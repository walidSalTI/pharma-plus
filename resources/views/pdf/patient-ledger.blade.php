<!DOCTYPE html>
<html dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Medical Ledger - {{ $user->f_name }} {{ $user->l_name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        h1 { color: #1a73e8; font-size: 20px; border-bottom: 2px solid #1a73e8; padding-bottom: 8px; }
        h2 { color: #2c3e50; font-size: 16px; margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #1a73e8; color: white; padding: 8px 12px; text-align: left; font-size: 11px; }
        td { padding: 8px 12px; border-bottom: 1px solid #e0e0e0; font-size: 11px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        .header { margin-bottom: 24px; }
        .header p { margin: 2px 0; color: #666; }
        .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #ccc; font-size: 10px; color: #999; text-align: center; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-green { background: #d4edda; color: #155724; }
        .badge-red { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PharmaPlus — Patient Medical Ledger</h1>
        <p><strong>Patient:</strong> {{ $user->f_name }} {{ $user->l_name }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Phone:</strong> {{ $user->phone_number }}</p>
        <p><strong>Blood Type:</strong> {{ $patient->blood_type ?? 'N/A' }}</p>
        <p><strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <h2>🩺 Chronic Conditions</h2>
    @if ($patient->chronicRecords->isEmpty())
        <p>No chronic conditions recorded.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Disease (EN)</th>
                    <th>Disease (AR)</th>
                    <th>Diagnosis Year</th>
                    <th>Severity</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($patient->chronicRecords as $record)
                    <tr>
                        <td>{{ $record->chronicDisease?->name_en ?? 'N/A' }}</td>
                        <td>{{ $record->chronicDisease?->name_ar ?? 'N/A' }}</td>
                        <td>{{ $record->diagnosis_year }}</td>
                        <td>{{ $record->severity ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>💊 Active Medications</h2>
    @if ($patient->medicationPatients->where('is_active', true)->isEmpty())
        <p>No active medications recorded.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>State</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($patient->medicationPatients->where('is_active', true) as $mp)
                    <tr>
                        <td>{{ $mp->medication?->product?->name ?? 'N/A' }}</td>
                        <td>{{ $mp->dosage }}</td>
                        <td>{{ $mp->frequency }}</td>
                        <td>{{ $mp->state }}</td>
                        <td>{{ $mp->start_date ?? 'N/A' }}</td>
                        <td>{{ $mp->end_date ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>📋 Order History</h2>
    @if ($patient->medicationOrders->isEmpty())
        <p>No order history.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Pharmacy</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($patient->medicationOrders as $order)
                    <tr>
                        <td>{{ $order->invoice_number }}</td>
                        <td>{{ $order->pharmacy?->name ?? 'N/A' }}</td>
                        <td>${{ number_format((float) $order->total_price, 2) }}</td>
                        <td>
                            <span class="badge {{ $order->status === 'completed' ? 'badge-green' : 'badge-red' }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>PharmaPlus — Smart Medical &amp; Pharmacy Management System</p>
        <p>This document contains confidential medical information.</p>
    </div>
</body>
</html>
