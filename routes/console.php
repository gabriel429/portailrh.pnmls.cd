<?php

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
