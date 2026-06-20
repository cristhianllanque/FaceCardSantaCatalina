<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Grado</th>
            <th>Sección</th>
            <th>Turno</th>
            <th>Hora de Ingreso</th>
            <th>Estado</th>
            <th>Confianza Facial</th>
        </tr>
    </thead>
    <tbody>
        @foreach($asistencias as $a)
        <tr>
            <td>{{ $a->persona->codigo }}</td>
            <td>{{ $a->persona->nombre }}</td>
            <td>{{ $a->persona->grado }}</td>
            <td>{{ $a->persona->seccion }}</td>
            <td>{{ ucfirst($a->turno) ?? '-' }}</td>
            <td>{{ $a->hora_ingreso }}</td>
            <td>{{ ucfirst($a->estado) }}</td>
            <td>{{ round($a->confianza * 100, 1) }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>
