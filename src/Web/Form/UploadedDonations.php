<?php

declare(strict_types=1);

namespace LotoSorter\Web\Form;

use LotoSorter\Web\WebSettings;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Joins the uploaded CSV exports (one per Google Sheet tab) into one CSV, in memory: nothing is stored on the server.
 */
final class UploadedDonations
{
    private const EXTENSIONS = ['csv', 'txt'];

    public function __construct(private readonly WebSettings $settings)
    {
    }

    /**
     * @throws InvalidUploadException
     */
    public function read(mixed $uploadedFiles): string
    {
        $files = $this->sentFiles($uploadedFiles);
        if ([] === $files) {
            throw new InvalidUploadException('Ajoutez au moins un fichier CSV de dons.');
        }
        if (count($files) > $this->settings->maxFiles) {
            throw new InvalidUploadException(sprintf('%d fichiers au maximum.', $this->settings->maxFiles));
        }

        return implode("\n", array_map(fn(UploadedFileInterface $file): string => $this->content($file), $files));
    }

    /**
     * @return list<UploadedFileInterface>
     */
    private function sentFiles(mixed $uploadedFiles): array
    {
        $files = $uploadedFiles instanceof UploadedFileInterface ? [$uploadedFiles] : (array) $uploadedFiles;

        return array_values(array_filter(
            $files,
            static fn(mixed $file): bool => $file instanceof UploadedFileInterface
                && UPLOAD_ERR_NO_FILE !== $file->getError(),
        ));
    }

    private function content(UploadedFileInterface $file): string
    {
        $name = (string) $file->getClientFilename();

        if (UPLOAD_ERR_OK !== $file->getError()) {
            throw new InvalidUploadException(sprintf('Le fichier « %s » n\'a pas pu être envoyé.', $name));
        }
        if ((int) $file->getSize() > $this->settings->maxFileBytes) {
            throw new InvalidUploadException(sprintf(
                'Le fichier « %s » dépasse %d Mo.',
                $name,
                intdiv($this->settings->maxFileBytes, 1_000_000),
            ));
        }
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
            throw new InvalidUploadException(sprintf('Le fichier « %s » n\'est pas un CSV.', $name));
        }

        return (string) $file->getStream();
    }
}
