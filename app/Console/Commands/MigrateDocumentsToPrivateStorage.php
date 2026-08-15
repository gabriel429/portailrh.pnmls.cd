<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-off migration for the fix that stopped storing agent documents in the
 * public webroot (see DocumentController::storeUploadedDocument). Documents
 * uploaded before that fix still sit under public/uploads/documents — this
 * moves them to the private 'local' disk and repoints the 'fichier' column,
 * closing the same web-accessibility gap for pre-existing files.
 *
 * Dry-run by default; pass --execute to actually move files and update rows.
 */
class MigrateDocumentsToPrivateStorage extends Command
{
    protected $signature = 'documents:migrate-to-private-storage
        {--execute : Déplace effectivement les fichiers et met à jour la base (sinon, aperçu seul)}
        {--keep-public : Conserve une copie du fichier public au lieu de la supprimer après migration}';

    protected $description = 'Migre les documents agent legacy de public/uploads/documents vers le stockage privé';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $keepPublic = (bool) $this->option('keep-public');

        $legacyDocuments = Document::query()
            ->where('fichier', 'like', 'uploads/%')
            ->orderBy('id')
            ->get();

        if ($legacyDocuments->isEmpty()) {
            $this->info('Aucun document legacy à migrer.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d document(s) legacy trouvé(s) sous public/uploads/.%s',
            $legacyDocuments->count(),
            $execute ? '' : ' (aperçu — utilisez --execute pour appliquer)'
        ));

        $migrated = 0;
        $missing = 0;
        $failed = 0;

        foreach ($legacyDocuments as $document) {
            $publicPath = public_path($document->fichier);

            if (!file_exists($publicPath)) {
                $missing++;
                $this->warn("  [manquant] #{$document->id} : {$document->fichier}");

                continue;
            }

            $filename = basename($document->fichier);
            $newPath = 'documents/' . $filename;

            if (!$execute) {
                $this->line("  [aperçu] #{$document->id} : {$document->fichier} -> (private) {$newPath}");
                $migrated++;

                continue;
            }

            try {
                $contents = file_get_contents($publicPath);
                if ($contents === false) {
                    throw new \RuntimeException('Lecture du fichier source impossible.');
                }

                Storage::disk('local')->put($newPath, $contents);

                if (!Storage::disk('local')->exists($newPath)
                    || Storage::disk('local')->size($newPath) !== filesize($publicPath)
                ) {
                    throw new \RuntimeException('La copie ne correspond pas au fichier source.');
                }

                $document->update(['fichier' => $newPath]);

                if (!$keepPublic) {
                    @unlink($publicPath);
                }

                $migrated++;
                $this->line("  [ok] #{$document->id} : {$document->fichier} -> (private) {$newPath}");
            } catch (\Throwable $exception) {
                $failed++;
                $this->error("  [échec] #{$document->id} : {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Terminé : %d migré(s), %d fichier(s) source introuvable(s), %d échec(s).',
            $migrated,
            $missing,
            $failed,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
