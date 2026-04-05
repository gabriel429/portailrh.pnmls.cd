<?php

use App\Models\ActivitePlan;
use App\Models\Province;
use App\Services\PtaDocxImportReader;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('agents:deduplicate {--execute : Supprime effectivement les doublons}', function () {
	$duplicateGroups = DB::table('agents')
		->selectRaw('MIN(id) as keep_id')
		->selectRaw('GROUP_CONCAT(id ORDER BY id) as grouped_ids')
		->selectRaw('COUNT(*) as total')
		->selectRaw('UPPER(TRIM(nom)) as nom_key')
		->selectRaw('UPPER(TRIM(prenom)) as prenom_key')
		->selectRaw('UPPER(TRIM(COALESCE(postnom, ""))) as postnom_key')
		->selectRaw('COALESCE(annee_naissance, 0) as annee_key')
		->selectRaw('UPPER(TRIM(COALESCE(lieu_naissance, ""))) as lieu_key')
		->groupByRaw('UPPER(TRIM(nom)), UPPER(TRIM(prenom)), UPPER(TRIM(COALESCE(postnom, ""))), COALESCE(annee_naissance, 0), UPPER(TRIM(COALESCE(lieu_naissance, "")))')
		->havingRaw('COUNT(*) > 1')
		->orderByDesc('total')
		->get();

	if ($duplicateGroups->isEmpty()) {
		$this->info('Aucun doublon detecte.');

		return;
	}

	$foreignKeys = collect(DB::select(
		'SELECT TABLE_NAME as table_name, COLUMN_NAME as column_name
		 FROM information_schema.KEY_COLUMN_USAGE
		 WHERE REFERENCED_TABLE_SCHEMA = ?
		   AND REFERENCED_TABLE_NAME = ?
		 ORDER BY TABLE_NAME, COLUMN_NAME',
		[DB::getDatabaseName(), 'agents']
	));

	$groups = $duplicateGroups->map(function ($group) {
		$ids = array_values(array_filter(array_map('intval', explode(',', (string) $group->grouped_ids))));
		$keepId = (int) $group->keep_id;

		return [
			'keep_id' => $keepId,
			'duplicate_ids' => array_values(array_filter($ids, fn (int $id) => $id !== $keepId)),
			'label' => trim(implode(' ', array_filter([
				$group->nom_key,
				$group->postnom_key,
				$group->prenom_key,
			]))),
			'annee' => (int) $group->annee_key,
			'lieu' => $group->lieu_key,
			'total' => (int) $group->total,
		];
	})->filter(fn (array $group) => $group['duplicate_ids'] !== [])->values();

	$duplicatesToDelete = $groups->sum(fn (array $group) => count($group['duplicate_ids']));

	$this->info(sprintf(
		'%d groupe(s) de doublons detecte(s), %d agent(s) en surplus.',
		$groups->count(),
		$duplicatesToDelete
	));

	foreach ($groups->take(10) as $group) {
		$this->line(sprintf(
			'- Garde #%d, supprime [%s] pour %s (%d, %s)',
			$group['keep_id'],
			implode(', ', $group['duplicate_ids']),
			$group['label'],
			$group['annee'],
			$group['lieu']
		));
	}

	if (!$this->option('execute')) {
		$this->comment('Mode simulation. Relancer avec --execute pour appliquer la deduplication.');

		return;
	}

	DB::transaction(function () use ($groups, $foreignKeys) {
		foreach ($groups as $group) {
			$keepId = $group['keep_id'];
			$duplicateIds = $group['duplicate_ids'];

			foreach ($foreignKeys as $foreignKey) {
				$table = $foreignKey->table_name;
				$column = $foreignKey->column_name;

				if ($table === 'agents') {
					continue;
				}

				foreach ($duplicateIds as $duplicateId) {
					DB::table($table)
						->where($column, $duplicateId)
						->update([$column => $keepId]);
				}
			}

			DB::table('agents')->whereIn('id', $duplicateIds)->delete();
		}
	});

	$this->info(sprintf(
		'Deduplication terminee: %d agent(s) supprime(s).',
		$duplicatesToDelete
	));
})->purpose('Supprime les doublons d\'agents en conservant l\'ID le plus ancien');

Artisan::command('pta:import-docx {file : Chemin vers le fichier DOCX PTA} {--annee=} {--createur=} {--execute : Importe effectivement les activites}', function (PtaDocxImportReader $reader) {
	$file = (string) $this->argument('file');
	$annee = $this->option('annee') ? (int) $this->option('annee') : null;
	$records = $reader->read($file, $annee);

	if ($records === []) {
		$this->warn('Aucune activite exploitable n\'a ete detectee dans le document PTA.');
		return;
	}

	$resolvedAnnee = (int) ($annee ?: ($records[0]['annee'] ?? now()->year));
	$provinceCatalog = Province::query()->get();
	$normalizeProvinceKey = static function (?string $value): string {
		$value = mb_strtoupper((string) $value);
		$value = preg_replace('/[^A-Z0-9]/u', '', $value) ?? $value;

		return str_replace([
			'KASAIORIENT',
			'SUDUBANGI',
			'BASUELE',
			'HAUTUELE',
		], [
			'KASAIORIENTAL',
			'SUDUBANGI',
			'BASUELE',
			'HAUTUELE',
		], $value);
	};
	$resolveProvinceIds = static function (array $provinceTexts) use ($provinceCatalog, $normalizeProvinceKey): array {
		$joined = $normalizeProvinceKey(implode(' ', $provinceTexts));
		$ids = [];

		foreach ($provinceCatalog as $province) {
			$variants = array_filter([
				$province->nom ?? null,
				$province->nom_province ?? null,
			]);

			foreach ($variants as $variant) {
				$key = $normalizeProvinceKey($variant);
				if ($key !== '' && str_contains($joined, $key)) {
					$ids[] = (int) $province->id;
					break;
				}
			}
		}

		return array_values(array_unique($ids));
	};
	$unresolvedProvinceSets = [];
	$records = collect($records)->map(function (array $record) use ($resolveProvinceIds, &$unresolvedProvinceSets) {
		$provinceIds = $resolveProvinceIds($record['province_names'] ?? []);
		if (($record['province_names'] ?? []) !== [] && $provinceIds === []) {
			$unresolvedProvinceSets[] = implode(' | ', $record['province_names']);
		}

		$record['province_ids'] = $provinceIds;
		return $record;
	});

	$this->info(sprintf('%d activite(s) detectee(s) pour l\'annee %d.', $records->count(), $resolvedAnnee));
	$this->line(sprintf('- SEN : %d', $records->where('niveau_administratif', 'SEN')->count()));
	$this->line(sprintf('- SEP : %d', $records->where('niveau_administratif', 'SEP')->count()));
	$this->line(sprintf('- Categories : %d', $records->pluck('categorie')->filter()->unique()->count()));

	foreach ($records->take(8) as $record) {
		$this->line(sprintf(
			'  • [%s] %s%s',
			$record['niveau_administratif'],
			$record['titre'],
			$record['categorie'] ? ' {' . $record['categorie'] . '}' : ''
		));
	}

	$unresolvedProvinceSets = collect($unresolvedProvinceSets)->unique()->values();
	if ($unresolvedProvinceSets->isNotEmpty()) {
		$this->warn('Provinces non resolues detectees :');
		foreach ($unresolvedProvinceSets as $item) {
			$this->line('  - ' . $item);
		}
	}

	if (!$this->option('execute')) {
		$this->comment('Mode simulation. Relancez avec --execute et --createur=ID pour enregistrer les activites.');
		return;
	}

	$createurId = (int) $this->option('createur');
	if ($createurId <= 0) {
		$this->error('L\'option --createur=ID est obligatoire en mode execution.');
		return;
	}

	$created = 0;
	$updated = 0;

	DB::transaction(function () use ($records, $createurId, &$created, &$updated) {
		foreach ($records as $record) {
			$match = [
				'annee' => $record['annee'],
				'niveau_administratif' => $record['niveau_administratif'],
				'titre' => $record['titre'],
				'categorie' => $record['categorie'],
			];

			$activite = ActivitePlan::query()->firstOrNew($match);
			$isNew = !$activite->exists;

			$payload = [
				'titre' => $record['titre'],
				'categorie' => $record['categorie'],
				'resultat_attendu' => $record['resultat_attendu'],
				'niveau_administratif' => $record['niveau_administratif'],
				'validation_niveau' => $record['validation_niveau'],
				'responsable_code' => $record['responsable_code'],
				'cout_cdf' => $record['cout_cdf'],
				'province_id' => $record['province_ids'][0] ?? null,
				'annee' => $record['annee'],
				'trimestre' => $record['trimestre'],
				'trimestre_1' => $record['trimestre_1'],
				'trimestre_2' => $record['trimestre_2'],
				'trimestre_3' => $record['trimestre_3'],
				'trimestre_4' => $record['trimestre_4'],
			];

			if ($isNew) {
				$payload['createur_id'] = $createurId;
				$payload['statut'] = 'planifiee';
				$payload['pourcentage'] = 0;
			}

			$activite->fill($payload);
			$activite->save();
			$activite->provinces()->sync($record['province_ids'] ?? []);

			if ($isNew) {
				$created++;
			} else {
				$updated++;
			}
		}
	});

	$this->info(sprintf('Import PTA termine : %d creee(s), %d mise(s) a jour.', $created, $updated));
})->purpose('Importe un document PTA Word en activites de plan de travail');

Artisan::command('pta:import-docx:helpers', function () {
	$this->line('Commande disponible : php artisan pta:import-docx "chemin/docx" --annee=2026 --createur=ID --execute');
})->purpose('Affiche un exemple d\'utilisation de l\'import PTA DOCX');
