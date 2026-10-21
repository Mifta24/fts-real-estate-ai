<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\HandoverRequest;
use App\Models\ViewingAppointment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('privacy:prune {--dry-run : Only report what would be removed}')]
#[Description('Delete old visitor conversations and erase personal details from past viewing requests (see config/retention.php).')]
class PrunePersonalData extends Command
{
    public const ERASED_NAME = '—';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $conversations = $this->staleConversations();
        $viewings = $this->staleViewings();

        $this->info(($dryRun ? 'Would delete ' : 'Deleting ').($conversations?->count() ?? 0).' conversation(s) and '.($dryRun ? 'erase ' : 'erasing ').($viewings?->count() ?? 0).' viewing contact record(s).');

        if ($dryRun) {
            return self::SUCCESS;
        }

        $conversations?->delete();

        $viewings?->update([
            'visitor_name' => self::ERASED_NAME,
            'visitor_email' => null,
            'visitor_phone' => null,
            'notes' => null,
            'conversation_id' => null,
        ]);

        return self::SUCCESS;
    }

    /**
     * @return ?Builder<Conversation>
     */
    private function staleConversations()
    {
        $days = (int) config('retention.conversation_days');

        if ($days <= 0) {
            return null;
        }

        return Conversation::query()
            ->whereRaw('coalesce(last_message_at, created_at) < ?', [now()->subDays($days)])
            ->whereDoesntHave('handoverRequest', fn ($query) => $query->where('status', HandoverRequest::STATUS_OPEN));
    }

    /**
     * @return ?Builder<ViewingAppointment>
     */
    private function staleViewings()
    {
        $days = (int) config('retention.viewing_days');

        if ($days <= 0) {
            return null;
        }

        return ViewingAppointment::query()
            ->whereDate('viewing_date', '<', now()->subDays($days)->toDateString())
            ->where('visitor_name', '!=', self::ERASED_NAME);
    }
}
