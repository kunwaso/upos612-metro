<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Prompts;

final class LaravelPrompts
{
    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function optimize_controller(?string $controller = null, ?string $route = null): array
    {
        $controllerText = $controller !== null && $controller !== '' ? $controller : '<controller_class>';
        $routeText = $route !== null && $route !== '' ? $route : '<route_name_or_method+path>';

        return [
            [
                'role' => 'user',
                'content' => "Optimize Laravel controller '{$controllerText}' for maintainability and performance. Use tools in this sequence: project_map, routes_list, route_details (target '{$routeText}'), controller_methods, controller_source, schema_snapshot. Propose minimal diffs and explain tradeoffs. If patching is needed, use apply_patch only when mode is PATCH, then run_pint, run_phpstan, and run_tests.",
            ],
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function migration_safety_check(?string $scope = null): array
    {
        $scopeText = $scope !== null && $scope !== '' ? $scope : 'core+modules';

        return [
            [
                'role' => 'user',
                'content' => "Perform migration safety review for scope '{$scopeText}'. Use migrations_status, migrations_list_files, migration_show (as needed), schema_snapshot, index_health, and show_create_table. Report: blocking risks, backwards compatibility issues, lock/index risks, rollout order, and rollback notes.",
            ],
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function perf_tuning_sql(?string $sql = null, ?string $table = null): array
    {
        $sqlText = $sql !== null && $sql !== '' ? $sql : '<select_query>';
        $tableText = $table !== null && $table !== '' ? $table : '<table_name>';

        return [
            [
                'role' => 'user',
                'content' => "Tune SQL performance for query '{$sqlText}' and table '{$tableText}'. Use explain_query, show_create_table, schema_snapshot, and index_health. Provide concrete index/query rewrites, expected impact, and potential side effects.",
            ],
        ];
    }
}
