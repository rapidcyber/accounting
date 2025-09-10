<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Models\Budget;

class ExpensesExport implements FromCollection, WithHeadings, WithEvents
{
    protected $parameters;
    public function __construct(array $params = [])
    {
        $this->parameters = $params;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $expenses = Expense::query();
        $params = $this->parameters;
        $args = $params;
        $expenses = $expenses->select(
            'id',
            \DB::raw("DATE_FORMAT(date, '%m/%d/%Y') as date"),
            'quantity',
            'unit',
            'description',
            'payment_method',
            'amount',
            'total_amount'
        )->when(
            $args['date_from'] ?? null,
            fn ($query, $dateFrom) => $query->whereDate('date', '>=', $dateFrom)
        )->when(
            $args['date_to'] ?? null,
            fn ($query, $dateTo) => $query->whereDate('date', '<=', $dateTo)
        );

        return $expenses->get();
    }
    /**
     * Define the headings for the export.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            '#',
            'DATE',
            'QUANTITY',
            'UNIT',
            'DESCRIPTION',
            'PAYMENT METHOD',
            'AMOUNT',
            'TOTAL AMOUNT',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // Center align all cells
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Auto size columns
                foreach (range('A', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Grand total row
                $expenses = $this->collection();
                $total = $expenses->sum('total_amount');
                $rowCount = $expenses->count() + 2; // +2 for heading row


                $sheet->setCellValue('G' . $rowCount, 'GRAND TOTAL:');
                $sheet->setCellValue('H' . $rowCount, $total);

                // Budget balance
                $budgetBalance = Budget::latest('date')->first()->amount ?? 0;
                $sheet->setCellValue('A' . $rowCount, 'CASH ON HAND:');
                $sheet->setCellValue('B' . $rowCount, $budgetBalance);

                // Insert 4 rows above
                $sheet->insertNewRowBefore(1, 4);

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');

                $sheet->setCellValue('A1', '6TH CONGRESSIONAL DISTRICT OFFICE');
                $sheet->setCellValue('A2', 'Dulong Bayan, Poblacion, Santa Maria, Bulacan');
                $sheet->setCellValue('A3', 'EXPENSES AS OF ' . now()->format('F, Y'));
                $sheet->setCellValue('G4', 'DATE:');
                $sheet->setCellValue('H4', now()->format('m/d/Y'));
                // Insert image (e.g., logo) at the top left corner
                $spLogo = new Drawing();
                $spLogo->setName('SP Logo');
                $spLogo->setDescription('Serbisyong kumPleyto Logo');
                $spLogo->setPath(public_path('images/sp_logo.png')); // Adjust the path as needed
                $spLogo->setHeight(60);
                $spLogo->setCoordinates('A1');
                $spLogo->setWorksheet($sheet);

                $hrpLogo = new Drawing();
                $hrpLogo->setName('HRP Logo');
                $hrpLogo->setDescription('House of Representatives Logo');
                $hrpLogo->setPath(public_path('images/hrp_logo.png')); // Adjust the path as needed
                $hrpLogo->setHeight(60);
                $hrpLogo->setCoordinates('H1');
                $hrpLogo->setWorksheet($sheet);

                // $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F6:F'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('G6:G'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('H6:H'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('F'.$sheet->getHighestRow().':G'.$sheet->getHighestRow())->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A5:G5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD3D3D3');
                $sheet->getStyle('A5:G5')->getFont()->setBold(true);
            }
        ];
    }
}
