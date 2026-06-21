<table>
    <thead>
        <tr>
            <th colspan="8" style="font-size: 16px; font-weight: bold; text-align: center;">REPORTE DE ASISTENCIA DIARIA - FACECARD</th>
        </tr>
        <tr>
            <th colspan="8" style="text-align: center;">Fecha de Reporte: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</th>
        </tr>
        <tr>
            <th colspan="8" style="text-align: center;">Total de Registros: {{ $asistencias->count() }} personas</th>
        </tr>
        <tr>
            <th></th> <!-- Espacio en blanco -->
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #8b5cf6; color: #ffffff;">Código</th>
            <th style="font-weight: bold; background-color: #8b5cf6; color: #ffffff;">Nombre y Apellidos</th>
            <th style="font-weight: bold; background-color: #8b5cf6; color: #ffffff;">Cargo</th>
            <th style="font-weight: bold; background-color: #8b5cf6; color: #ffffff;">Grado / Área</th>
            <th style="font-weight: bold; background-color: #8b5cf6; color: #ffffff;">Sección</th>
            <th style="font-weight: bold; background-color: #8b5cf6; color: #ffffff;">Turno</th>
            <th style="font-weight: bold; background-color: #8b5cf6; color: #ffffff;">Hora de Ingreso</th>
            <th style="font-weight: bold; background-color: #8b5cf6; color: #ffffff;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($asistencias as $a)
        <tr>
            <td>{{ $a->persona->codigo }}</td>
            <td>{{ $a->persona->nombre }}</td>
            <td>{{ ucfirst($a->persona->cargo) }}</td>
            <td>
                @if($a->persona->cargo == 'estudiante')
                    {{ $a->persona->grado }}° Grado
                @else
                    {{ $a->persona->area ?? '-' }}
                @endif
            </td>
            <td>{{ $a->persona->cargo == 'estudiante' ? $a->persona->seccion : '-' }}</td>
            <td>{{ ucfirst($a->turno) ?? '-' }}</td>
            <td>{{ $a->hora_ingreso }}</td>
            <td>{{ ucfirst($a->estado) }}</td>
        </tr>
        @endforeach
        @if($asistencias->isEmpty())
        <tr>
            <td colspan="8" style="text-align: center;">No se encontraron registros para los filtros seleccionados.</td>
        </tr>
        @endif
    </tbody>
</table>
