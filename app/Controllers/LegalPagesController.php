<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Páginas legales públicas (Meta Developer exige privacidad, términos y eliminación de datos).
 */
class LegalPagesController extends Controller
{
    public function privacyPolicy()
    {
        return view('legal/privacy_policy', $this->legalViewData('Política de privacidad'));
    }

    public function termsOfService()
    {
        return view('legal/terms_of_service', $this->legalViewData('Condiciones del servicio'));
    }

    public function dataDeletion()
    {
        return view('legal/data_deletion', $this->legalViewData('Eliminación de datos'));
    }

    /**
     * @return array{title: string, legalEntity: string, contactEmail: string, lastUpdatedLabel: string}
     */
    private function legalViewData(string $title): array
    {
        return [
            'title'            => $title,
            'legalEntity'      => $this->legalEnv('LEGAL_ENTITY_NAME', 'Tu Asesor RM'),
            'contactEmail'     => $this->legalEnv('LEGAL_CONTACT_EMAIL', 'migliorerossana@tuasesorrm.com.ve'),
            'lastUpdatedLabel' => $this->legalEnv('LEGAL_PAGES_LAST_UPDATED', '8 de septiembre de 2026'),
        ];
    }

    private function legalEnv(string $key, string $default): string
    {
        $value = function_exists('env') ? env($key) : null;
        if ($value === null || $value === false || $value === '') {
            $fromGetenv = getenv($key);
            $value = ($fromGetenv !== false) ? $fromGetenv : $default;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }
}
