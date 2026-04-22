import { Head, Link } from '@inertiajs/react';

const features = [
    {
        title: 'Emissor de Notas',
        description: 'Emissao fiscal integrada para deixar a operacao da padaria mais segura e profissional.',
        icon: 'bi bi-receipt-cutoff',
    },
    {
        title: 'Controle de Vendas',
        description: 'Acompanhe vendas, comandas, recebimentos e movimentacoes do dia com rapidez.',
        icon: 'bi bi-cart-check',
    },
    {
        title: 'Relatorios Gerenciais',
        description: 'Visualize indicadores, faturamento, desempenho de produtos e resultados por unidade.',
        icon: 'bi bi-bar-chart-line',
    },
    {
        title: 'Controle de Funcionarios',
        description: 'Gerencie acessos, unidades, jornadas e permissoes de cada colaborador.',
        icon: 'bi bi-people',
    },
    {
        title: 'Emissao de Contra-Cheque',
        description: 'Organize a rotina do RH com emissao de contra-cheque e apoio na gestao de pagamentos.',
        icon: 'bi bi-file-earmark-text',
    },
    {
        title: 'Financeiro Completo',
        description: 'Tenha controle financeiro, despesas, boletos, contas e acompanhamento do caixa.',
        icon: 'bi bi-cash-coin',
    },
];

const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

export default function Welcome({ selectedUnitId = null, planSettings = {} }) {
    const currentYear = new Date().getFullYear();
    const loginUrl = selectedUnitId ? route('login', { l: selectedUnitId }) : route('login');
    const plans = [
        {
            title: 'Plano Mensal',
            items: [
                `Loja Matriz: ${formatCurrency(planSettings?.matrix_monthly_price ?? 250)}`,
                `Filiais: ${formatCurrency(planSettings?.branch_monthly_price ?? 180)}`,
            ],
            highlight: 'Ideal para operar com suporte continuo e evolucao constante.',
        },
        {
            title: 'Licenca de Compra',
            items: [
                `Matriz: ${formatCurrency(planSettings?.purchase_matrix_price ?? 10000)}`,
                `Filiais: ${formatCurrency(planSettings?.purchase_branch_price ?? 5000)} cada`,
                `Parcelamento em ate ${Number(planSettings?.purchase_installments ?? 15)}x`,
            ],
            highlight: 'Opcao para quem deseja adquirir a solucao com investimento unico.',
        },
    ];

    return (
        <>
            <Head title="PDV: Padaria de Verdade">
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link
                    href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Urbanist:wght@400;500;600;700&display=swap"
                    rel="stylesheet"
                />
                <link
                    rel="stylesheet"
                    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
                />
            </Head>

            <div
                className="min-h-screen bg-white text-slate-900"
                style={{
                    fontFamily: "'Urbanist', sans-serif",
                    '--brand-green': '#198754',
                    '--brand-green-soft': '#e8f7ef',
                    '--brand-orange': '#f97316',
                    '--brand-orange-soft': '#fff1e8',
                    '--brand-cream': '#fffdf8',
                }}
            >
                <div className="pointer-events-none absolute inset-x-0 top-0 h-[420px] bg-[radial-gradient(circle_at_top_left,rgba(25,135,84,0.16),transparent_40%),radial-gradient(circle_at_top_right,rgba(249,115,22,0.18),transparent_38%)]" />

                <header className="relative z-10 border-b border-emerald-100 bg-white/85 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-[0.35em] text-emerald-700">
                                PDV
                            </p>
                            <h1
                                className="text-xl font-extrabold text-slate-900 sm:text-2xl"
                                style={{ fontFamily: "'Outfit', sans-serif" }}
                            >
                                Padaria de Verdade
                            </h1>
                        </div>

                        <div className="flex items-center gap-3">
                            <a
                                href="https://wa.me/351913007661"
                                target="_blank"
                                rel="noreferrer"
                                className="hidden rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 sm:inline-flex"
                            >
                                WhatsApp
                            </a>
                            <Link
                                href={loginUrl}
                                className="rounded-full bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-800"
                            >
                                Login
                            </Link>
                        </div>
                    </div>
                </header>

                <main className="relative z-10">
                    <section className="mx-auto max-w-7xl px-6 pb-16 pt-12 lg:pb-24 lg:pt-16">
                        <div className="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                            <div className="reveal-up">
                                <span className="inline-flex rounded-full border border-orange-200 bg-orange-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.3em] text-orange-700">
                                    Sistema completo para padarias
                                </span>

                                <h2
                                    className="mt-6 max-w-3xl text-4xl font-extrabold leading-tight text-slate-900 sm:text-5xl lg:text-6xl"
                                    style={{ fontFamily: "'Outfit', sans-serif" }}
                                >
                                    Gestao profissional para quem quer vender mais, organizar melhor e crescer com seguranca.
                                </h2>

                                <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                                    O <strong>PDV: Padaria de Verdade</strong> foi pensado para matrizes e filiais,
                                    com operacao simples no dia a dia e recursos completos para vendas, fiscal,
                                    financeiro e gestao de equipe.
                                </p>

                                <div className="mt-8 flex flex-wrap gap-4">
                                    <Link
                                        href={loginUrl}
                                        className="rounded-full bg-emerald-700 px-6 py-3 text-sm font-bold uppercase tracking-[0.18em] text-white shadow-xl shadow-emerald-700/20 transition hover:bg-emerald-800"
                                    >
                                        Acessar sistema
                                    </Link>
                                    <a
                                        href="https://wa.me/351913007661"
                                        target="_blank"
                                        rel="noreferrer"
                                        className="rounded-full border border-orange-200 bg-white px-6 py-3 text-sm font-bold uppercase tracking-[0.18em] text-orange-700 transition hover:bg-orange-50"
                                    >
                                        Falar no WhatsApp
                                    </a>
                                </div>

                                <div className="mt-10 grid gap-4 sm:grid-cols-3">
                                    <div className="rounded-3xl border border-emerald-100 bg-[var(--brand-green-soft)] p-5">
                                        <p className="text-sm font-bold uppercase tracking-[0.22em] text-emerald-700">
                                            Fiscal
                                        </p>
                                        <p className="mt-2 text-sm text-slate-600">
                                            Emissor de notas e apoio ao processo fiscal.
                                        </p>
                                    </div>
                                    <div className="rounded-3xl border border-orange-100 bg-[var(--brand-orange-soft)] p-5">
                                        <p className="text-sm font-bold uppercase tracking-[0.22em] text-orange-700">
                                            Operacao
                                        </p>
                                        <p className="mt-2 text-sm text-slate-600">
                                            PDV, vendas, relatorios e controle financeiro.
                                        </p>
                                    </div>
                                    <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                        <p className="text-sm font-bold uppercase tracking-[0.22em] text-slate-700">
                                            Equipe
                                        </p>
                                        <p className="mt-2 text-sm text-slate-600">
                                            Funcionarios, permissoes e emissao de contra-cheque.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="reveal-up">
                                <div className="rounded-[32px] border border-white bg-[var(--brand-cream)] p-6 shadow-2xl shadow-emerald-100/60">
                                    <div className="rounded-[28px] bg-slate-900 p-6 text-white">
                                        <p className="text-xs font-bold uppercase tracking-[0.32em] text-emerald-300">
                                            Solucao multiempresa
                                        </p>
                                        <h3
                                            className="mt-3 text-3xl font-extrabold"
                                            style={{ fontFamily: "'Outfit', sans-serif" }}
                                        >
                                            Matriz e filiais no mesmo ecossistema.
                                        </h3>
                                        <p className="mt-4 text-sm leading-7 text-slate-300">
                                            Cada empresa opera de forma isolada, com controle por matriz, filiais,
                                            usuarios e unidades vinculadas ao seu proprio ambiente.
                                        </p>
                                    </div>

                                    <div className="mt-5 grid gap-4">
                                        <div className="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-xl text-emerald-700">
                                                    <i className="bi bi-buildings" aria-hidden="true" />
                                                </span>
                                                <div>
                                                    <p className="font-bold text-slate-900">Estrutura para crescimento</p>
                                                    <p className="text-sm text-slate-600">
                                                        Ideal para quem deseja comecar com uma loja e expandir com filiais.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-orange-100 text-xl text-orange-700">
                                                    <i className="bi bi-shield-check" aria-hidden="true" />
                                                </span>
                                                <div>
                                                    <p className="font-bold text-slate-900">Controle e seguranca</p>
                                                    <p className="text-sm text-slate-600">
                                                        Perfis, permissoes, operacao por unidade e isolamento entre empresas.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-700">
                                                    <i className="bi bi-graph-up-arrow" aria-hidden="true" />
                                                </span>
                                                <div>
                                                    <p className="font-bold text-slate-900">Visao gerencial</p>
                                                    <p className="text-sm text-slate-600">
                                                        Relatorios e acompanhamento para decidir com base em dados.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="border-y border-emerald-100 bg-emerald-50/50">
                        <div className="mx-auto max-w-7xl px-6 py-16">
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                                <div className="reveal-up">
                                    <p className="text-sm font-bold uppercase tracking-[0.28em] text-emerald-700">
                                        O que o sistema oferece
                                    </p>
                                    <h3
                                        className="mt-3 text-3xl font-extrabold text-slate-900 sm:text-4xl"
                                        style={{ fontFamily: "'Outfit', sans-serif" }}
                                    >
                                        Recursos que ajudam a padaria a operar com mais controle.
                                    </h3>
                                </div>
                            </div>

                            <div className="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {features.map((feature, index) => (
                                    <article
                                        key={feature.title}
                                        className="reveal-up rounded-[28px] border border-white bg-white p-6 shadow-md shadow-emerald-100/60"
                                        style={{ animationDelay: `${Math.min(index * 0.06, 0.24)}s` }}
                                    >
                                        <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-100 text-2xl text-orange-700">
                                            <i className={feature.icon} aria-hidden="true" />
                                        </span>
                                        <h4 className="mt-5 text-xl font-bold text-slate-900">
                                            {feature.title}
                                        </h4>
                                        <p className="mt-3 text-sm leading-7 text-slate-600">
                                            {feature.description}
                                        </p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-6 py-16 lg:py-20">
                        <div className="grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
                            <div className="reveal-up rounded-[32px] border border-orange-100 bg-[var(--brand-orange-soft)] p-8">
                                <p className="text-sm font-bold uppercase tracking-[0.28em] text-orange-700">
                                    Planos e investimento
                                </p>
                                <h3
                                    className="mt-4 text-3xl font-extrabold text-slate-900"
                                    style={{ fontFamily: "'Outfit', sans-serif" }}
                                >
                                    Escolha entre mensalidade ou compra da solucao.
                                </h3>
                                <p className="mt-4 text-sm leading-7 text-slate-700">
                                    A hospedagem e de responsabilidade do comprador, com custo de
                                    <strong> {` ${formatCurrency(planSettings?.hosting_monthly_price ?? 70)} mensais`}</strong>.
                                </p>
                                <p className="mt-4 text-sm leading-7 text-slate-700">
                                    As condicoes comerciais podem ser apresentadas com mais detalhes no atendimento.
                                </p>
                            </div>

                            <div className="grid gap-6 md:grid-cols-2">
                                {plans.map((plan, index) => (
                                    <div
                                        key={plan.title}
                                        className="reveal-up rounded-[32px] border border-emerald-100 bg-white p-8 shadow-lg shadow-emerald-100/50"
                                        style={{ animationDelay: `${0.08 + index * 0.08}s` }}
                                    >
                                        <p className="text-sm font-bold uppercase tracking-[0.28em] text-emerald-700">
                                            {plan.title}
                                        </p>
                                        <div className="mt-6 space-y-4">
                                            {plan.items.map((item) => (
                                                <div key={item} className="flex items-start gap-3 rounded-2xl bg-slate-50 px-4 py-4">
                                                    <span className="mt-0.5 text-emerald-700">
                                                        <i className="bi bi-check-circle-fill" aria-hidden="true" />
                                                    </span>
                                                    <p className="text-sm font-semibold text-slate-800">{item}</p>
                                                </div>
                                            ))}
                                        </div>
                                        <p className="mt-6 text-sm leading-7 text-slate-600">
                                            {plan.highlight}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="bg-slate-900">
                        <div className="mx-auto grid max-w-7xl gap-8 px-6 py-16 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div className="reveal-up">
                                <p className="text-sm font-bold uppercase tracking-[0.3em] text-emerald-300">
                                    Contato
                                </p>
                                <h3
                                    className="mt-4 text-3xl font-extrabold text-white sm:text-4xl"
                                    style={{ fontFamily: "'Outfit', sans-serif" }}
                                >
                                    Vamos colocar sua padaria para operar com mais controle e mais resultado.
                                </h3>
                                <div className="mt-8 grid gap-4 sm:grid-cols-3">
                                    <a
                                        href="https://wa.me/351913007661"
                                        target="_blank"
                                        rel="noreferrer"
                                        className="rounded-3xl border border-white/10 bg-white/5 p-5 text-sm text-slate-200 transition hover:bg-white/10"
                                    >
                                        <p className="font-bold text-white">WhatsApp</p>
                                        <p className="mt-2">+351 913 007 661</p>
                                    </a>
                                    <a
                                        href="mailto:cleriodias@gmail.com"
                                        className="rounded-3xl border border-white/10 bg-white/5 p-5 text-sm text-slate-200 transition hover:bg-white/10"
                                    >
                                        <p className="font-bold text-white">E-mail</p>
                                        <p className="mt-2">cleriodias@gmail.com</p>
                                    </a>
                                    <a
                                        href="https://clerio.com.br"
                                        target="_blank"
                                        rel="noreferrer"
                                        className="rounded-3xl border border-white/10 bg-white/5 p-5 text-sm text-slate-200 transition hover:bg-white/10"
                                    >
                                        <p className="font-bold text-white">Site</p>
                                        <p className="mt-2">clerio.com.br</p>
                                    </a>
                                </div>
                            </div>

                            <div className="reveal-up">
                                <div className="rounded-[32px] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-black/20 backdrop-blur">
                                    <p className="text-xs font-bold uppercase tracking-[0.3em] text-orange-300">
                                        Comece agora
                                    </p>
                                    <p className="mt-3 text-sm leading-7 text-slate-200">
                                        Fale conosco para apresentar sua operacao e receber a proposta ideal para
                                        matriz e filiais.
                                    </p>
                                    <div className="mt-6 flex flex-col gap-3">
                                        <a
                                            href="https://wa.me/351913007661"
                                            target="_blank"
                                            rel="noreferrer"
                                            className="rounded-full bg-emerald-600 px-5 py-3 text-center text-sm font-bold uppercase tracking-[0.18em] text-white transition hover:bg-emerald-700"
                                        >
                                            Solicitar atendimento
                                        </a>
                                        <Link
                                            href={loginUrl}
                                            className="rounded-full border border-white/20 px-5 py-3 text-center text-sm font-bold uppercase tracking-[0.18em] text-white transition hover:bg-white/10"
                                        >
                                            Ja sou cliente
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-slate-200 bg-white">
                    <div className="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            © {currentYear} PDV: Padaria de Verdade. Todos os direitos reservados.
                        </p>
                        <p className="font-semibold text-emerald-700">clerio.com.br</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
