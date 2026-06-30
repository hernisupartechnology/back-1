<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body    { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1F3864; }
        h1      { color: #1F3864; border-bottom: 2px solid #BF9000; padding-bottom: 6px; }
        h2      { color: #BF9000; font-size: 12px; margin-top: 16px; }
        table   { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th      { background-color: #1F3864; color: white; padding: 5px; text-align: left; }
        td      { padding: 4px 5px; border-bottom: 1px solid #eee; }
        .total  { font-weight: bold; color: #BF9000; }
        .balance-pos { color: #2E7D32; }
        .balance-neg { color: #C62828; }
        .footer { margin-top: 24px; text-align: center; font-size: 8px; color: #757575; }
    </style>
</head>
<body>
    <h1>UparFinanzas — {{ $historial->title }}</h1>
    <p><strong>Período:</strong> {{ $historial->period_label }}</p>
    <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</p>

    {{-- Resumen General --}}
    <h2>Resumen General</h2>
    <table>
        <tr>
            <td>Total Ingresos</td>
            <td class="total">${{ number_format($datos['total_incomes'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Gastos</td>
            <td class="total">${{ number_format($datos['total_expenses'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Balance</td>
            <td class="{{ $datos['balance'] >= 0 ? 'balance-pos' : 'balance-neg' }}">
                ${{ number_format(abs($datos['balance']), 0, ',', '.') }}
                {{ $datos['balance'] >= 0 ? '▲' : '▼' }}
            </td>
        </tr>
    </table>

    {{-- Ingresos --}}
    @if($datos['incomes']->count() > 0)
    <h2>Ingresos</h2>
    <table>
        <thead>
            <tr><th>Categoría</th><th>Descripción</th><th>Monto</th><th>Fecha</th></tr>
        </thead>
        <tbody>
            @foreach($datos['incomes'] as $ingreso)
            <tr>
                <td>{{ $ingreso->category?->name }}</td>
                <td>{{ $ingreso->description }}</td>
                <td>${{ number_format($ingreso->amount, 0, ',', '.') }}</td>
                <td>{{ $ingreso->received_date?->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Gastos --}}
    @if($datos['expenses']->count() > 0)
    <h2>Gastos</h2>
    <table>
        <thead>
            <tr><th>Categoría</th><th>Descripción</th><th>Monto</th><th>Estado</th><th>Vencimiento</th></tr>
        </thead>
        <tbody>
            @foreach($datos['expenses'] as $gasto)
            <tr>
                <td>{{ $gasto->category?->name }}</td>
                <td>{{ $gasto->description }}</td>
                <td>${{ number_format($gasto->amount, 0, ',', '.') }}</td>
                <td>{{ $gasto->is_paid ? 'Pagado' : 'Pendiente' }}</td>
                <td>{{ $gasto->due_date?->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        © {{ date('Y') }} UparFinanzas · Desarrollado por UparTechnology
    </div>
</body>
</html>
