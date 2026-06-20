<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 6px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <h2>Reporte de Asistencia</h2>
    <p><strong>Fecha:</strong> {{ $fecha }}</p>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Turno</th>
                <th>Hora</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($asistencias as $a)
            <tr>
                <td>{{ $a->persona->codigo }}</td>
                <td>{{ $a->persona->nombre }}</td>
                <td>{{ ucfirst($a->turno) ?? '-' }}</td>
                <td>{{ $a->hora_ingreso }}</td>
                <td>{{ ucfirst($a->estado) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
