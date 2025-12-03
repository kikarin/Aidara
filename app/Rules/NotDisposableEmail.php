<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NotDisposableEmail implements Rule
{
    /**
     * Daftar domain email disposable/temporary yang umum
     */
    private array $disposableDomains = [
        '10minutemail.com',
        'tempmail.com',
        'guerrillamail.com',
        'mailinator.com',
        'throwaway.email',
        'temp-mail.org',
        'getnada.com',
        'mohmal.com',
        'fakeinbox.com',
        'trashmail.com',
        'yopmail.com',
        'sharklasers.com',
        'grr.la',
        'guerrillamailblock.com',
        'pokemail.net',
        'spam4.me',
        'bccto.me',
        'chammy.info',
        'devnullmail.com',
        'dispostable.com',
        'emailondeck.com',
        'fakemailgenerator.com',
        'getairmail.com',
        'inboxkitten.com',
        'maildrop.cc',
        'mailforspam.com',
        'mintemail.com',
        'mytrashmail.com',
        'putthisinyourspamdatabase.com',
        'spamgourmet.com',
        'spambox.us',
        'tempail.com',
        'tempmail.net',
        'tempmailo.com',
        'tmpmail.net',
        'tmpmail.org',
        'mail-temp.com',
        'throwawaymail.com',
        'tempr.email',
        'meltmail.com',
        'emailtemp.org',
        'tempinbox.co.uk',
        'temp-mail.io',
        'tempmailaddress.com',
        'tempmailer.com',
        'tempmailer.de',
        'temp-mail.ru',
        'tempail.com',
        'tempmailo.com',
        'tmpmail.org',
        'tmpmail.net',
        'mohmal.com',
        'guerrillamail.info',
        'guerrillamail.biz',
        'guerrillamail.net',
        'guerrillamail.org',
        'guerrillamail.de',
        'guerrillamailblock.com',
        'pokemail.net',
        'spam4.me',
        'bccto.me',
        'chammy.info',
        'devnullmail.com',
        'dispostable.com',
        'emailondeck.com',
        'fakemailgenerator.com',
        'getairmail.com',
        'inboxkitten.com',
        'maildrop.cc',
        'mailforspam.com',
        'mintemail.com',
        'mytrashmail.com',
        'putthisinyourspamdatabase.com',
        'spamgourmet.com',
        'spambox.us',
    ];

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = substr(strrchr($value, "@"), 1);
        $domain = strtolower($domain);

        return !in_array($domain, $this->disposableDomains);
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Email yang digunakan tidak diizinkan. Silakan gunakan email asli Anda (seperti Gmail, Yahoo, Outlook).';
    }
}

