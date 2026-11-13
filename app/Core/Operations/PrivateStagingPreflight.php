<?php
declare(strict_types=1);

namespace App\Core\Operations;

/**
 * Read-only staging readiness evaluator. Status is never an approval to deploy.
 * Env and database facts must be collected independently by a trusted operator.
 */
final class PrivateStagingPreflight
{
    public static function assess(array $configuration,array $facts):array
    {
        $missing=[];
        $requiredConfiguration=[
            'APP_ENV'=>'homologation',
            'APP_DEBUG'=>'false',
            'IMAGINE_HOMOLOGATION_PREPARE'=>'1',
            'IMAGINE_PUBLIC_INTAKE_ENABLED'=>'0',
            'IMAGINE_DELIVERY_WORKER_ENABLED'=>'0',
            'IMAGINE_SYNTHETIC_DATA_ONLY'=>'1',
            'DB_CONNECTION'=>'pgsql',
        ];
        foreach($requiredConfiguration as $name=>$value){
            if(($configuration[$name]??null)!==$value)$missing[]='CONFIG_'. $name;
        }
        foreach([
            'private_database_address','database_connected','database_name_matched',
            'postgres16_or_later','non_privileged_role','membership_rls_enabled',
            'membership_rls_forced','tenant_context_empty','tls_or_loopback',
        ] as $condition){
            if(($facts[$condition]??null)!==true)$missing[]='DB_'.strtoupper($condition);
        }
        return [
            'schema'=>'imagine.crm.private-staging-preflight.v1',
            'status'=>$missing===[]?'READ_ONLY_PREFLIGHT_PASSED_REVIEW_REQUIRED':'NO_GO',
            'failedChecks'=>$missing,
            'canDeploy'=>false,
            'canEnablePublicIntake'=>false,
            'canActivateWorker'=>false,
            'realLeadPersistenceVerified'=>false,
            'functionalHomologationVerified'=>false,
        ];
    }
}
