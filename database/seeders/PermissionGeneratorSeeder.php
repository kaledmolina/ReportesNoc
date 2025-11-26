<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionGeneratorSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            'incident',
            'report',
            'report_puerto_libertador',
            'report_regional',
            'user',
            'role', // Área
            'permission',
        ];

        $actions = [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
        ];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$resource}";
                Permission::firstOrCreate(['name' => $permissionName]);
            }
        }

        // Permiso especial para ver todos los incidentes (sin restricción de asignación)
        Permission::firstOrCreate(['name' => 'view_all_incidents']);

        // Permisos para Widgets
        $widgets = [
            'view_widget_stats_overview',
            'view_widget_active_tickets',
            'view_widget_clipboard',
            'view_widget_concentrator',
            'view_widget_provider',
            'view_widget_server',
            'view_widget_tv',
            'view_widget_latest_report',
        ];

        foreach ($widgets as $widget) {
            Permission::firstOrCreate(['name' => $widget]);
        }

        // Asignar todo al super admin (redundante si usamos Gate, pero útil)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());
        
        $this->command->info('Permissions generated successfully.');
    }
}
