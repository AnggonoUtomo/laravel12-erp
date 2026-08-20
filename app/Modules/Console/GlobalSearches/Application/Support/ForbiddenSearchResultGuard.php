<?php

namespace App\Modules\Console\GlobalSearches\Application\Support;

use App\Modules\Console\GlobalSearches\Application\DTOs\SearchResult;

class ForbiddenSearchResultGuard
{
    private const FORBIDDEN_PATTERN = '/\b(password|password_hash|remember_token|token|secret|api[_-]?key|apikey|private_key|signed_url|storage_path|backup_path|backup_content|document_content|document_number_raw|audit_old_values|audit_new_values|email_body|mail_payload)\b/i';

    public function isSafe(SearchResult $result): bool
    {
        return ! $this->containsForbiddenText($result->toArray());
    }

    private function containsForbiddenText(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if ($this->containsForbiddenText($key) || $this->containsForbiddenText($item)) {
                    return true;
                }
            }

            return false;
        }

        if (is_string($value)) {
            return preg_match(self::FORBIDDEN_PATTERN, $value) === 1;
        }

        return false;
    }
}
