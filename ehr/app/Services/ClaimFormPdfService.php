<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ClaimFormPdfService
{
    public function __construct(
        private ClaimFormBuilder $builder
    ) {}

    public function buildPdfForEncounter(\App\Models\Encounter $encounter, string $formType): string
    {
        $payload = $this->builder->buildForEncounter($encounter, $formType);

        return $this->renderPdf($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function renderPdf(array $payload): string
    {
        $script = base_path('scripts/fill_claim_pdf.py');
        if (! is_file($script)) {
            throw new \RuntimeException('Claim form PDF script is missing.');
        }

        $inputFile = tempnam(sys_get_temp_dir(), 'docrev_claim_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'docrev_claim_out_');
        if ($inputFile === false || $outputFile === false) {
            throw new \RuntimeException('Unable to create temporary files for PDF generation.');
        }

        file_put_contents($inputFile, json_encode($payload));

        try {
            $python = $this->pythonBinary();
            $process = new Process([
                $python,
                $script,
                '--input', $inputFile,
                '--output', $outputFile,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::error('Claim PDF generation failed', [
                    'stderr' => $process->getErrorOutput(),
                    'stdout' => $process->getOutput(),
                ]);
                throw new \RuntimeException('Unable to generate claim form PDF: '.trim($process->getErrorOutput()));
            }

            if (! is_file($outputFile)) {
                throw new \RuntimeException('Claim form PDF generator did not produce output.');
            }

            $pdf = file_get_contents($outputFile);
            if ($pdf === false || $pdf === '' || strncmp($pdf, '%PDF', 4) !== 0) {
                throw new \RuntimeException('Claim form PDF generator returned invalid output.');
            }

            return $pdf;
        } finally {
            @unlink($inputFile);
            @unlink($outputFile);
        }
    }

    private function pythonBinary(): string
    {
        $configured = config('claim_forms.python_path');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $process = new Process(['where.exe', 'python']);
            $process->run();
            if ($process->isSuccessful()) {
                foreach (preg_split('/\R/', trim($process->getOutput())) as $line) {
                    $line = trim($line);
                    if ($line === '' || ! is_file($line)) {
                        continue;
                    }
                    if (str_contains(strtolower($line), 'windowsapps')) {
                        continue;
                    }

                    return $line;
                }
            }

            foreach ($this->windowsPythonCandidates() as $candidate) {
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return 'python3';
        }

        throw new \RuntimeException(
            'Python not found for claim form PDFs. Install Python 3, run pip install -r scripts/requirements.txt, '.
            'and set PYTHON_PATH in .env using forward slashes (e.g. C:/Users/You/.../python.exe).'
        );
    }

    /**
     * @return list<string>
     */
    private function windowsPythonCandidates(): array
    {
        $profile = getenv('USERPROFILE') ?: '';
        $candidates = [];

        if ($profile !== '') {
            $glob = glob($profile.'\\AppData\\Local\\Programs\\Python\\Python*\\python.exe') ?: [];
            $candidates = array_merge($candidates, $glob);
        }

        $candidates[] = 'C:\\Python312\\python.exe';
        $candidates[] = 'C:\\Python311\\python.exe';

        return $candidates;
    }
}
