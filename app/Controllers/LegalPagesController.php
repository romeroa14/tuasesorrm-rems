<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Páginas legales públicas (Meta Developer exige URL de privacidad y términos al publicar la app).
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

    /**
     * @return array{title: string, legalEntity: string, contactEmail: string, lastUpdatedLabel: string}
     */
    private function legalViewData(string $title): array
    {
        $entity = getenv('LEGAL_ENTITY_NAME');
        $entity = ($entity !== false && trim((string) $entity) !== '')
            ? trim((string) $entity)
            : 'Tu Asesor RM';

        $email = getenv('LEGAL_CONTACT_EMAIL');
        $email = ($email !== false && trim((string) $email) !== '')
            ? trim((string) $email)
            : '';

        $updated = getenv('LEGAL_PAGES_LAST_UPDATED');
        $lastUpdatedLabel = ($updated !== false && trim((string) $updated) !== '')
            ? trim((string) $updated)
            : '29 de abril de 2026';

        return [
            'title'              => $title,
            'legalEntity'        => $entity,
            'contactEmail'       => $email,
            'lastUpdatedLabel'   => $lastUpdatedLabel,
        ];
    }
}
