<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitialSystemSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('tb28_aplicacoes')->updateOrInsert(
            ['tb28_id' => 1],
            [
                'tb28_nome' => 'Padaria + NFe',
                'tb28_slug' => 'padaria-nfe',
                'tb28_rota_inicial' => 'dashboard',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('tb28_aplicacoes')->updateOrInsert(
            ['tb28_id' => 2],
            [
                'tb28_nome' => 'Padaria',
                'tb28_slug' => 'padaria',
                'tb28_rota_inicial' => 'padaria',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('tb28_aplicacoes')->updateOrInsert(
            ['tb28_id' => 3],
            [
                'tb28_nome' => 'NFe',
                'tb28_slug' => 'nfe',
                'tb28_rota_inicial' => 'nfe?unit_id={unit_id}',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('billing_plan_settings')->updateOrInsert(
            ['id' => 1],
            [
                'matrix_monthly_price' => 250.00,
                'branch_monthly_price' => 180.00,
                'hosting_monthly_price' => 70.00,
                'purchase_matrix_price' => 10000.00,
                'purchase_branch_price' => 5000.00,
                'purchase_installments' => 15,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('tb_17_configuracao_descarte')->updateOrInsert(
            ['id' => 1],
            [
                'percentual_aceitavel' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('matrizes')->updateOrInsert(
            ['id' => 1],
            [
                'nome' => 'DASH',
                'slug' => 'dash',
                'cnpj' => '00.000.000/0000-00',
                'tb28_id' => 1,
                'status' => 1,
                'plano_mensal_valor' => 0,
                'plano_contratado_em' => null,
                'pagamento_ativo' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('tb2_unidades')->updateOrInsert(
            ['tb2_id' => 1],
            [
                'matriz_id' => 1,
                'tb2_tipo' => 'matriz',
                'tb2_nome' => 'DASH',
                'tb2_endereco' => 'Endereco ficticio da matriz DASH',
                'tb2_cep' => '72000-000',
                'tb2_fone' => '(61) 90000-0001',
                'tb2_cnpj' => '00.000.000/0000-00',
                'tb2_localizacao' => 'https://maps.google.com/?q=DASH+Matriz',
                'tb2_status' => 1,
                'plano_mensal_valor' => 0,
                'plano_contratado_em' => null,
                'pagamento_ativo' => false,
                'login_liberado' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('tb2_unidades')->updateOrInsert(
            ['tb2_id' => 2],
            [
                'matriz_id' => 1,
                'tb2_tipo' => 'filial',
                'tb2_nome' => 'DASH Filial',
                'tb2_endereco' => 'Endereco ficticio da filial DASH',
                'tb2_cep' => '72000-001',
                'tb2_fone' => '(61) 90000-0002',
                'tb2_cnpj' => '00.000.000/0001-00',
                'tb2_localizacao' => 'https://maps.google.com/?q=DASH+Filial',
                'tb2_status' => 1,
                'plano_mensal_valor' => 0,
                'plano_contratado_em' => null,
                'pagamento_ativo' => false,
                'login_liberado' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('users')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Clerio',
                'email' => 'cleriodias@gmail.com',
                'email_verified_at' => null,
                'password' => Hash::make('080010'),
                'funcao' => 7,
                'funcao_original' => 7,
                'hr_ini' => '00:00:00',
                'hr_fim' => '23:00:00',
                'salario' => 0,
                'vr_cred' => 0,
                'tb2_id' => 1,
                'matriz_id' => 1,
                'cod_acesso' => '8010',
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('tb2_unidade_user')->updateOrInsert(
            ['user_id' => 1, 'tb2_id' => 1],
            ['created_at' => $now, 'updated_at' => $now]
        );

        DB::table('tb2_unidade_user')->updateOrInsert(
            ['user_id' => 1, 'tb2_id' => 2],
            ['created_at' => $now, 'updated_at' => $now]
        );
    }
}
