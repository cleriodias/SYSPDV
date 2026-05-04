<?php

namespace Tests\Feature;

use App\Models\Aplicacao;
use App\Models\CobrancaMensal;
use App\Models\Matriz;
use App\Models\Unidade;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BossDashboardRecurringBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_generates_monthly_charges_from_contract_date_for_matrix_and_branch(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-04 09:00:00'));

        $boss = $this->makeBossUser();
        $matriz = Matriz::create([
            'nome' => 'Empresa Recorrente',
            'slug' => 'empresa-recorrente',
            'tb28_id' => Aplicacao::PADARIA_NFE,
            'status' => 1,
            'pagamento_ativo' => true,
            'plano_mensal_valor' => 250,
            'plano_contratado_em' => '2026-02-15 00:00:00',
        ]);

        Unidade::create([
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'matriz',
            'tb2_nome' => 'Matriz Empresa Recorrente',
            'tb2_endereco' => 'Rua A, 100',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000199',
            'tb2_localizacao' => 'https://maps.google.com/?q=Matriz+Empresa+Recorrente',
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
            'plano_mensal_valor' => 250,
            'plano_contratado_em' => '2026-02-15 00:00:00',
        ]);

        Unidade::create([
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'filial',
            'tb2_nome' => 'Filial Norte',
            'tb2_endereco' => 'Rua B, 200',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 98888-7777',
            'tb2_cnpj' => '12345678000198',
            'tb2_localizacao' => 'https://maps.google.com/?q=Filial+Norte',
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
            'plano_mensal_valor' => 180,
            'plano_contratado_em' => '2026-03-20 00:00:00',
        ]);

        $response = $this
            ->actingAs($boss)
            ->withSession($this->withBossSession($boss))
            ->get(route('dashboard'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Boss/Dashboard')
            ->where('matrizes.0.payment_status_key', 'atrasado')
            ->where('matrizes.0.payment_due_at', '2026-05-15')
            ->where('matrizes.0.payment_pending_count', 4)
            ->where('matrizes.0.payment_overdue_count', 3)
            ->where('matrizes.0.branches.0.payment_status_key', 'atrasado')
            ->where('matrizes.0.branches.0.payment_due_at', '2026-05-20')
            ->where('matrizes.0.branches.0.payment_pending_count', 3)
            ->where('matrizes.0.branches.0.payment_overdue_count', 2)
        );

        $this->assertSame(4, CobrancaMensal::query()
            ->where('referencia_tipo', CobrancaMensal::TIPO_MATRIZ)
            ->where('referencia_id', $matriz->id)
            ->count());
        $this->assertSame(3, CobrancaMensal::query()
            ->where('referencia_tipo', CobrancaMensal::TIPO_FILIAL)
            ->where('matriz_id', $matriz->id)
            ->count());
        $this->assertDatabaseHas('tb32_cobrancas_mensais', [
            'matriz_id' => $matriz->id,
            'referencia_tipo' => CobrancaMensal::TIPO_MATRIZ,
            'referencia_id' => $matriz->id,
            'competencia' => '2026-05-01',
            'data_vencimento' => '2026-05-15',
            'valor_cobrado' => 250.00,
            'status_pagamento' => CobrancaMensal::STATUS_PENDENTE,
        ]);
        $this->assertDatabaseHas('tb32_cobrancas_mensais', [
            'matriz_id' => $matriz->id,
            'referencia_tipo' => CobrancaMensal::TIPO_FILIAL,
            'competencia' => '2026-05-01',
            'data_vencimento' => '2026-05-20',
            'valor_cobrado' => 180.00,
            'status_pagamento' => CobrancaMensal::STATUS_PENDENTE,
        ]);
    }

    public function test_boss_can_mark_current_matrix_cycle_as_paid_and_reopen_it(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-04 09:00:00'));

        $boss = $this->makeBossUser();
        $matriz = Matriz::create([
            'nome' => 'Empresa Toggle',
            'slug' => 'empresa-toggle',
            'tb28_id' => Aplicacao::PADARIA,
            'status' => 1,
            'pagamento_ativo' => false,
            'plano_mensal_valor' => 250,
            'plano_contratado_em' => '2026-04-15 00:00:00',
        ]);

        Unidade::create([
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'matriz',
            'tb2_nome' => 'Matriz Empresa Toggle',
            'tb2_endereco' => 'Rua C, 300',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 97777-6666',
            'tb2_cnpj' => '12345678000197',
            'tb2_localizacao' => 'https://maps.google.com/?q=Matriz+Empresa+Toggle',
            'tb2_status' => 1,
            'pagamento_ativo' => false,
            'login_liberado' => true,
            'plano_mensal_valor' => 250,
            'plano_contratado_em' => '2026-04-15 00:00:00',
        ]);

        $this
            ->actingAs($boss)
            ->withSession($this->withBossSession($boss))
            ->put(route('settings.billing-status.matrices.payment', $matriz))
            ->assertRedirect();

        $this->assertDatabaseHas('tb32_cobrancas_mensais', [
            'matriz_id' => $matriz->id,
            'referencia_tipo' => CobrancaMensal::TIPO_MATRIZ,
            'referencia_id' => $matriz->id,
            'competencia' => '2026-04-01',
            'status_pagamento' => CobrancaMensal::STATUS_PAGO,
        ]);
        $this->assertDatabaseHas('tb32_cobrancas_mensais', [
            'matriz_id' => $matriz->id,
            'referencia_tipo' => CobrancaMensal::TIPO_MATRIZ,
            'referencia_id' => $matriz->id,
            'competencia' => '2026-05-01',
            'status_pagamento' => CobrancaMensal::STATUS_PAGO,
        ]);
        $this->assertDatabaseHas('matrizes', [
            'id' => $matriz->id,
            'pagamento_ativo' => 1,
        ]);

        $this
            ->actingAs($boss)
            ->withSession($this->withBossSession($boss))
            ->put(route('settings.billing-status.matrices.payment', $matriz))
            ->assertRedirect();

        $this->assertDatabaseHas('tb32_cobrancas_mensais', [
            'matriz_id' => $matriz->id,
            'referencia_tipo' => CobrancaMensal::TIPO_MATRIZ,
            'referencia_id' => $matriz->id,
            'competencia' => '2026-04-01',
            'status_pagamento' => CobrancaMensal::STATUS_PAGO,
        ]);
        $this->assertDatabaseHas('tb32_cobrancas_mensais', [
            'matriz_id' => $matriz->id,
            'referencia_tipo' => CobrancaMensal::TIPO_MATRIZ,
            'referencia_id' => $matriz->id,
            'competencia' => '2026-05-01',
            'status_pagamento' => CobrancaMensal::STATUS_PENDENTE,
        ]);
        $this->assertDatabaseHas('matrizes', [
            'id' => $matriz->id,
            'pagamento_ativo' => 0,
        ]);
    }

    private function makeBossUser(): User
    {
        $unit = Unidade::create([
            'tb2_nome' => 'DASH',
            'matriz_id' => null,
            'tb2_tipo' => 'filial',
            'tb2_endereco' => 'Endereco DASH',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 90000-0000',
            'tb2_cnpj' => '00000000000000',
            'tb2_localizacao' => 'https://maps.google.com/?q=DASH',
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
        ]);

        $user = User::factory()->create([
            'id' => 1,
            'name' => 'Boss Teste',
            'email' => 'boss@example.com',
            'funcao' => 7,
            'funcao_original' => 7,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => null,
            'cod_acesso' => 'BOSS7',
        ]);

        $user->units()->sync([$unit->tb2_id]);

        return $user;
    }

    private function withBossSession(User $boss): array
    {
        return [
            'active_role' => 7,
            'active_unit' => [
                'id' => $boss->tb2_id,
                'name' => 'DASH',
                'address' => 'Endereco DASH',
                'cnpj' => '00000000000000',
            ],
        ];
    }
}
