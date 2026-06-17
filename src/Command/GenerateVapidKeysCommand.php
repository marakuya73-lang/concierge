<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-vapid-keys',
    description: 'Generate VAPID keys for admin web push notifications',
)]
class GenerateVapidKeysCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!extension_loaded('openssl')) {
            $io->error('The openssl PHP extension is required to generate VAPID keys.');

            return Command::FAILURE;
        }

        $keys = $this->generateVapidKeys();

        $io->title('VAPID keys for Web Push');
        $io->writeln('Add these to your .env file:');
        $io->newLine();
        $io->writeln(sprintf('VAPID_PUBLIC_KEY=%s', $keys['publicKey']));
        $io->writeln(sprintf('VAPID_PRIVATE_KEY=%s', $keys['privateKey']));
        $io->writeln('VAPID_SUBJECT=mailto:your@email.com');
        $io->newLine();
        $io->note('Replace VAPID_SUBJECT with your contact email (required by push services).');
        $io->note('Push notifications also require: composer install --no-dev (minishlink/web-push).');

        return Command::SUCCESS;
    }

    /** @return array{publicKey: string, privateKey: string} */
    private function generateVapidKeys(): array
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        if (false === $key) {
            throw new \RuntimeException('Failed to generate EC key: '.(openssl_error_string() ?: 'unknown error'));
        }

        $details = openssl_pkey_get_details($key);
        if (false === $details || !isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            throw new \RuntimeException('Failed to read EC key details.');
        }

        $publicKey = "\x04".$details['ec']['x'].$details['ec']['y'];
        $privateKey = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);

        return [
            'publicKey' => $this->base64UrlEncode($publicKey),
            'privateKey' => $this->base64UrlEncode($privateKey),
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
