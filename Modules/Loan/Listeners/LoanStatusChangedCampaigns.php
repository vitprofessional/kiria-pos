<?php

namespace Modules\Loan\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

class LoanStatusChangedCampaigns implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object $event
     * @return void
     */
    public function handle($event)
    {
        
    }
}
