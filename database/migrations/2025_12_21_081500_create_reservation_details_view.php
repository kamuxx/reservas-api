<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("DROP VIEW IF EXISTS reservation_details_view;");

        $concat = DB::connection()->getDriverName() === 'sqlite' ? "r.event_date || ' ' || r.start_time" : "CONCAT(r.event_date, ' ', r.start_time)";
        $concatEnd = DB::connection()->getDriverName() === 'sqlite' ? "r.event_date || ' ' || r.end_time" : "CONCAT(r.event_date, ' ', r.end_time)";

        DB::statement("CREATE VIEW reservation_details_view AS
        SELECT 
            r.uuid AS reservation_uuid,
            r.event_name,
            r.event_description,
            r.event_date,
            r.start_time,
            r.end_time,
            $concat AS start_datetime,
            $concatEnd AS end_datetime,
            r.event_price,
            r.cancellation_reason,
            r.cancellation_by,
            r.created_at AS reservation_created_at,
            r.updated_at AS reservation_updated_at,
            
            -- Información del usuario
            u.uuid AS user_uuid,
            u.name AS user_name,
            u.email AS user_email,
            u.phone AS user_phone,
            
            -- Información del espacio
            s.uuid AS space_uuid,
            s.name AS space_name,
            s.description AS space_description,
            s.capacity AS space_capacity,
            
            -- Tipo de espacio
            st.uuid AS space_type_uuid,
            st.name AS space_type_name,
            
            -- Estado
            rs.uuid AS status_uuid,
            rs.name AS status_name,
            
            -- Regla de precios
            pr.uuid AS pricing_rule_uuid,
            pr.name AS pricing_rule_name,
            
            -- Imagen principal
            (SELECT si.image 
             FROM space_images si 
             WHERE si.space_id = s.uuid AND si.is_main = 1 
             LIMIT 1) AS space_main_image
            
        FROM reservation r
        JOIN users u ON r.reserved_by = u.uuid
        JOIN spaces s ON r.space_id = s.uuid
        JOIN space_types st ON s.spaces_type_id = st.uuid
        JOIN status rs ON r.status_id = rs.uuid
        LEFT JOIN pricing_rules pr ON r.pricing_rule_id = pr.uuid
        WHERE r.deleted_at IS NULL;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS reservation_details_view');
    }
};
