<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Services\Advisor\AdvisorService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('advisor:chat {agency? : Agency slug} {--locale=id : id|en|ja}')]
#[Description('Talk to the AI Property Advisor for an agency from the terminal, for testing the tool-calling loop without the web UI.')]
class AdvisorChatCommand extends Command
{
    public function handle(AdvisorService $service): int
    {
        $agency = $this->argument('agency')
            ? Agency::where('slug', $this->argument('agency'))->first()
            : Agency::first();

        if (! $agency) {
            $this->error('No agency found. Seed one first: php artisan db:seed');

            return self::FAILURE;
        }

        if (blank(config('services.local_llm.base_url'))) {
            $this->error('LOCAL_LLM_BASE_URL is not set in .env — the advisor has no model endpoint to call.');

            return self::FAILURE;
        }

        $locale = $this->option('locale');
        $conversation = $service->startConversation($agency, $locale);

        $this->info("Chatting with the AI Property Advisor for {$agency->name} ({$locale}). Type 'exit' to quit.");
        $this->newLine();

        while (true) {
            $visitorMessage = $this->ask('You');

            if ($visitorMessage === null || in_array(trim($visitorMessage), ['exit', 'quit'], true)) {
                break;
            }

            $message = $service->reply($agency, $conversation, $visitorMessage);

            $this->newLine();
            $this->line('<fg=cyan>Advisor:</> '.($message->content ?? '(no text — see UI payload below)'));

            if ($message->ui_payload) {
                $this->line('<fg=gray>[ui_payload] '.json_encode($message->ui_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</>');
            }

            $conversation->refresh();
            if ($conversation->isHandedOver()) {
                $this->warn('Conversation handed over to a human agent. Ending session.');
                break;
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }
}
