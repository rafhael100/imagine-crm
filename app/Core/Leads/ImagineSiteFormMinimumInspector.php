<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Channel-specific minimum draft check: an Imagine Site form requires a
 * name and at least one plausible e-mail or telephone candidate.
 *
 * The general CRM LeadDraftFields contract intentionally remains optional.
 * This inspector is offline, purely structural and NEVER enables intake.
 *
 * Telephone validation is an explicitly provisional syntax heuristic, not a
 * phone-number verification or approved final country-formatting policy.
 */
final class ImagineSiteFormMinimumInspector
{
    public function inspect(LeadDraftFields $draft): ImagineSiteFormMinimumAssessment
    {
        $name = $draft->name;
        $email = $draft->email;
        $phone = $draft->phone;

        $hasName = $name !== null && trim($name) !== '';
        $validEmail = $email !== null
            && $email === trim($email)
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $validPhone = $this->plausiblePhone($phone);

        return new ImagineSiteFormMinimumAssessment(
            namePresent: $hasName,
            emailCandidateValid: $validEmail,
            phoneCandidateValid: $validPhone,
            invalidSuppliedContact: ($email !== null && !$validEmail)
                || ($phone !== null && !$validPhone),
        );
    }

    private function plausiblePhone(?string $phone): bool
    {
        if ($phone === null
            || preg_match('/\A\+?[0-9][0-9 ()-]{0,39}\z/D', $phone) !== 1) {
            return false;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        $count = strlen($digits);

        // Provisional LAB-only range, not a real identity/contact check.
        return $count >= 8 && $count <= 15;
    }
}
