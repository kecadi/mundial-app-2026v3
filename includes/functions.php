<?php
// includes/functions.php

/**
 * Calcula la tabla de posiciones de un grupo dado un array de partidos.
 * @param array $equipos Array con datos de los equipos del grupo (id, name, flag...)
 * @param array $partidos Array con los partidos (pueden ser reales o predicciones)
 * @return array Tabla ordenada por Puntos > Diferencia Goles > Goles a Favor
 */
function calcularTablaGrupo($equipos, $partidos) {
    // 1. Inicializar tabla para los equipos de ESTE grupo
    $tabla = [];
    foreach ($equipos as $equipo) {
        $tabla[$equipo['id']] = [
            'id' => $equipo['id'],
            'nombre' => $equipo['name'],
            'bandera' => $equipo['flag'],
            'pj' => 0, 'g' => 0, 'e' => 0, 'p' => 0, 
            'gf' => 0, 'gc' => 0, 'dg' => 0, 'pts' => 0
        ];
    }

    // 2. Procesar partidos
    foreach ($partidos as $match) {
        $id_local = $match['team_home_id'];
        $id_visit = $match['team_away_id'];

        // --- CORRECCIÓN CRÍTICA ---
        // Si alguno de los equipos del partido NO está en la tabla actual (es decir, es de otro grupo),
        // saltamos este partido y pasamos al siguiente.
        if (!isset($tabla[$id_local]) || !isset($tabla[$id_visit])) {
            continue; 
        }
        // --------------------------

        // Detectar goles (reales o predichos)
        $goles_local = $match['predicted_home_score'] ?? $match['home_score'] ?? null;
        $goles_visit = $match['predicted_away_score'] ?? $match['away_score'] ?? null;

        if (is_null($goles_local) || is_null($goles_visit)) continue;

        // Sumar estadísticas Local
        $tabla[$id_local]['pj']++;
        $tabla[$id_local]['gf'] += $goles_local;
        $tabla[$id_local]['gc'] += $goles_visit;
        $tabla[$id_local]['dg'] = $tabla[$id_local]['gf'] - $tabla[$id_local]['gc'];

        // Sumar estadísticas Visitante
        $tabla[$id_visit]['pj']++;
        $tabla[$id_visit]['gf'] += $goles_visit;
        $tabla[$id_visit]['gc'] += $goles_local;
        $tabla[$id_visit]['dg'] = $tabla[$id_visit]['gf'] - $tabla[$id_visit]['gc'];

        // Puntos
        if ($goles_local > $goles_visit) {
            $tabla[$id_local]['g']++;
            $tabla[$id_local]['pts'] += 3;
            $tabla[$id_visit]['p']++;
        } elseif ($goles_local < $goles_visit) {
            $tabla[$id_visit]['g']++;
            $tabla[$id_visit]['pts'] += 3;
            $tabla[$id_local]['p']++;
        } else {
            $tabla[$id_local]['e']++;
            $tabla[$id_local]['pts'] += 1;
            $tabla[$id_visit]['e']++;
            $tabla[$id_visit]['pts'] += 1;
        }
    }

    // 3. Ordenar
    usort($tabla, function($a, $b) {
        if ($a['pts'] != $b['pts']) return $b['pts'] - $a['pts'];
        if ($a['dg'] != $b['dg']) return $b['dg'] - $a['dg'];
        return $b['gf'] - $a['gf'];
    });

    return $tabla;
}

/**
 * Obtiene el conteo total de comentarios para los partidos de una fase.
 * @param PDO $pdo Objeto de conexión PDO.
 * @param string $phase Fase actual (ej: 'group').
 * @return array Mapeo de [match_id => count].
 */
function get_comment_counts($pdo, $phase, $user_id) {
    $stmt = $pdo->prepare("SELECT 
        mc.match_id, 
        COUNT(mc.id) as unread_count 
    FROM match_comments mc
    JOIN matches m ON mc.match_id = m.id
    
    /* LEFT JOIN para obtener la fecha de la última lectura del usuario */
    LEFT JOIN user_read_status urs ON urs.match_id = mc.match_id AND urs.user_id = ?
    
    /* CÓNDICIÓN CLAVE: El comentario es más nuevo que la última lectura */
    WHERE m.phase = ? 
      AND mc.created_at > COALESCE(urs.last_read_at, '1970-01-01 00:00:00')
      
    GROUP BY mc.match_id");
    
    $stmt->execute([$user_id, $phase]);
    $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return $counts;
}

function get_phase_points(array $points_map, string $phase_name): int {
    // Usamos ?? 0 para manejar el caso en que la clave no exista.
    return (int)($points_map[$phase_name] ?? 0);
}