<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CoreLeadExport implements FromCollection, WithHeadings
{
    protected $coreLeads;

    public function __construct(Collection $coreLeads)
    {
        $this->coreLeads = $coreLeads;
    }

    public function headings(): array
    {
        return [
            trans('public.date_added'),
            trans('public.lead_id'),
            trans('public.categories'),
            trans('public.first_name'),
            trans('public.surname'),
            trans('public.email'),
            trans('public.telephone'),
            trans('public.country'),
            trans('public.referrer'),
        ];
    }

    public function collection(): Collection
    {
        $rows = [];

        foreach ($this->coreLeads as $lead) {
            $rows[] = [
                $lead->date_added,
                $lead->lead_id,
                $lead->categories,
                $lead->first_name,
                $lead->surname,
                $lead->email,
                $lead->telephone,
                $lead->country,
                $lead->referrer,
            ];
        }

        return collect($rows);
    }
}
