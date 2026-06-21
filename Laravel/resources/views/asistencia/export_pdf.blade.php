<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia</title>
    <style>
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 11px; 
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #8b5cf6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #8b5cf6;
            margin: 0;
            font-size: 22px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: #666;
        }
        .info-bar {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 12px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        th, td { 
            padding: 8px 10px; 
            border-bottom: 1px solid #e2e8f0; 
            text-align: left; 
        }
        th { 
            background-color: #f1f5f9; 
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .badge {
            padding: 3px 6px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-puntual { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-tardanza { background-color: #fef08a; color: #854d0e; border: 1px solid #fde047; }
        .badge-falta { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    
    <div class="header">
        <h1>FaceCard - Institución Educativa</h1>
        <p>Reporte Oficial de Asistencia Diaria</p>
    </div>

    <div class="info-bar">
        <strong>Fecha de Reporte:</strong> {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}<br>
        <strong>Total de Registros:</strong> {{ $asistencias->count() }} personas
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Código</th>
                <th style="width: 25%;">Nombre y Apellidos</th>
                <th style="width: 15%;">Cargo / Área</th>
                <th style="width: 15%;">Grado / Secc.</th>
                <th style="width: 10%;">Turno</th>
                <th style="width: 10%;">Hora</th>
                <th style="width: 10%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($asistencias as $a)
            <tr>
                <td style="font-family: monospace; font-size: 12px; color: #8b5cf6;">{{ $a->persona->codigo }}</td>
                <td style="font-weight: bold;">{{ $a->persona->nombre }}</td>
                <td>
                    @if($a->persona->cargo == 'docente')
                        Docente ({{ $a->persona->area ?? '-' }})
                    @else
                        Estudiante
                    @endif
                </td>
                <td>
                    @if($a->persona->cargo == 'estudiante')
                        {{ $a->persona->grado }}° "{{ $a->persona->seccion }}"
                    @else
                        -
                    @endif
                </td>
                <td>{{ ucfirst($a->turno) ?? '-' }}</td>
                <td style="font-family: monospace;">{{ $a->hora_ingreso }}</td>
                <td>
                    @php
                        $badgeClass = 'badge-puntual';
                        if($a->estado == 'tardanza') $badgeClass = 'badge-tardanza';
                        if($a->estado == 'falta') $badgeClass = 'badge-falta';
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ ucfirst($a->estado) }}</span>
                </td>
            </tr>
            @endforeach
            @if($asistencias->isEmpty())
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">No se encontraron registros para los filtros seleccionados.</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Generado por FaceCard v2.0 - Sistema de Reconocimiento Facial
    </div>

</body>
</html>
