<?php

namespace App\Services\Viewing;

use App\Models\Agency;
use App\Models\ViewingAppointment;

/**
 * Builds the WhatsApp / phone / email links that carry a viewing request
 * summary (or a plain "talk to an agent" request) to the agency team.
 */
class ContactLinks
{
    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    public function forViewing(Agency $agency, ViewingAppointment $viewing, string $locale): array
    {
        return $this->links($agency, $this->viewingMessage($viewing, $locale), $this->text('viewing', $locale).' '.$viewing->reference);
    }

    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    public function forAgent(Agency $agency, string $locale): array
    {
        return $this->links($agency, $this->text('agent_message', $locale), $this->text('agent', $locale));
    }

    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    private function links(Agency $agency, string $message, string $subject): array
    {
        $whatsapp = preg_replace('/\D+/', '', (string) $agency->whatsapp);
        $phone = preg_replace('/[^\d+]/', '', (string) $agency->phone);

        return [
            'whatsapp_url' => $whatsapp !== '' ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($message) : null,
            'phone_url' => $phone !== '' ? 'tel:'.$phone : null,
            'email_url' => filled($agency->email) ? 'mailto:'.$agency->email.'?subject='.rawurlencode($subject).'&body='.rawurlencode($message) : null,
        ];
    }

    private function viewingMessage(ViewingAppointment $viewing, string $locale): string
    {
        $viewing->loadMissing('property');

        $labels = match ($locale) {
            'en' => ['Hello, I would like to schedule a property viewing.', 'Name', 'Property', 'Date', 'Time', 'Visitors', 'Payment plan', 'Notes', 'Reference'],
            'ja' => ['こんにちは。物件の内見を予約したいです。', 'お名前', '物件', '日付', '時間', '人数', 'お支払い方法', 'ご要望', '受付番号'],
            default => ['Halo, saya ingin menjadwalkan survei properti.', 'Nama', 'Properti', 'Tanggal', 'Jam', 'Jumlah orang', 'Rencana pembayaran', 'Catatan', 'Referensi'],
        };

        $plans = [
            'en' => ['cash' => 'Cash', 'mortgage' => 'Mortgage (KPR)', 'undecided' => 'Not decided yet'],
            'ja' => ['cash' => '現金', 'mortgage' => '住宅ローン（KPR）', 'undecided' => '未定'],
            'id' => ['cash' => 'Tunai', 'mortgage' => 'KPR', 'undecided' => 'Belum ditentukan'],
        ][$locale];

        return implode("\n", [
            $labels[0],
            '',
            "{$labels[1]}: {$viewing->visitor_name}",
            "{$labels[2]}: ".$viewing->property->translatedName($locale),
            "{$labels[3]}: ".$viewing->viewing_date->locale($locale)->translatedFormat('l, j F Y'),
            "{$labels[4]}: {$viewing->viewing_time}",
            "{$labels[5]}: {$viewing->party_size}",
            "{$labels[6]}: ".($plans[$viewing->payment_plan] ?? $viewing->payment_plan),
            "{$labels[7]}: ".($viewing->notes ?: '-'),
            '',
            "{$labels[8]}: {$viewing->reference}",
        ]);
    }

    private function text(string $key, string $locale): string
    {
        return [
            'en' => ['viewing' => 'Viewing request', 'agent' => 'Question for a property agent', 'agent_message' => 'Hello, I would like to speak with a property agent.'],
            'ja' => ['viewing' => '内見リクエスト', 'agent' => '担当者へのご相談', 'agent_message' => 'こんにちは。不動産の担当者と話したいです。'],
            'id' => ['viewing' => 'Permintaan survei', 'agent' => 'Pertanyaan untuk agen properti', 'agent_message' => 'Halo, saya ingin bicara dengan agen properti.'],
        ][$locale][$key];
    }
}
