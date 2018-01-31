<?php
App::uses('AppShell', 'Console/Command');

class CommissionAlertTask extends AppShell
{
    public $uses = array(
        'DemandInfo',
        'CommissionInfo',
        'MCorp',
        'CommissionTelSupport',
        'CommissionVisitSupport',
        'CommissionOrderSupport',
        'AuctionInfo',
        'VisitTime',
        'MCommissionAlert'
    );

    const PHASE_TEL = 0;
    const PHASE_VISIT = 1;
    const PHASE_ORDER = 2;
    const STATUS_DEFAULT = -1;

    public function __formatStringBuilder($v, $u)
    {
        return "P" . (($u == 'M' || $u == 'H') ? "T" : "") . $v . $u;
    }

}
