import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { fetchRevenueDashboard } from '../src/services/api';
import type { RevenueChartPoint, RevenueDashboard, RevenueMetric, RevenueSummary } from '../src/types';

const formatCurrency = (value: number) =>
  Number(value ?? 0).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  });

function MetricCard({
  metric,
  accent,
  secondary = false,
}: {
  metric: RevenueMetric;
  accent: string;
  secondary?: boolean;
}) {
  return (
    <View style={[styles.metricCard, secondary ? styles.metricCardSecondary : styles.metricCardPrimary]}>
      <Text style={[styles.metricLabel, secondary ? styles.metricLabelSecondary : null]}>{metric.label}</Text>
      <Text style={[styles.metricValue, { color: accent }]}>{formatCurrency(metric.total)}</Text>
    </View>
  );
}

function SummarySection({
  eyebrow,
  title,
  summary,
  secondaryTitle,
}: {
  eyebrow: string;
  title: string;
  summary: RevenueSummary;
  secondaryTitle: string;
}) {
  return (
    <View style={styles.sectionCard}>
      <Text style={styles.sectionEyebrow}>{eyebrow}</Text>
      <Text style={styles.sectionTitle}>{title}</Text>
      <View style={styles.heroMetric}>
        <Text style={styles.heroMetricLabel}>Entradas em destaque</Text>
        <Text style={styles.heroMetricValue}>{formatCurrency(summary.entries_total)}</Text>
        <Text style={styles.heroMetricCaption}>{summary.period.label}</Text>
      </View>

      <View style={styles.metricGrid}>
        {summary.highlights.map((metric, index) => (
          <MetricCard
            key={`primary-${metric.key}`}
            metric={metric}
            accent={index === 0 ? '#166534' : '#1D4ED8'}
          />
        ))}
      </View>

      <Text style={styles.secondaryGroupTitle}>{secondaryTitle}</Text>
      <View style={styles.metricGrid}>
        {summary.secondary.map((metric) => (
          <MetricCard key={`secondary-${metric.key}`} metric={metric} accent="#92400E" secondary />
        ))}
      </View>
    </View>
  );
}

function ChartSection({
  title,
  hint,
  items,
}: {
  title: string;
  hint: string;
  items: RevenueChartPoint[];
}) {
  const maxValue = useMemo(() => {
    const biggest = items.reduce((carry, item) => Math.max(carry, item.dinheiro, item.cartao), 0);
    return biggest > 0 ? biggest : 1;
  }, [items]);

  return (
    <View style={styles.sectionCard}>
      <Text style={styles.sectionTitle}>{title}</Text>
      <Text style={styles.chartHint}>{hint}</Text>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chartScroll}>
        {items.map((item) => {
          const cashHeight = Math.max((item.dinheiro / maxValue) * 140, item.dinheiro > 0 ? 10 : 0);
          const cardHeight = Math.max((item.cartao / maxValue) * 140, item.cartao > 0 ? 10 : 0);

          return (
            <View key={item.label} style={styles.chartColumn}>
              <View style={styles.chartBars}>
                <View style={styles.chartTrack}>
                  <View style={[styles.chartFill, { height: cashHeight, backgroundColor: '#16A34A' }]} />
                </View>
                <View style={styles.chartTrack}>
                  <View style={[styles.chartFill, { height: cardHeight, backgroundColor: '#2563EB' }]} />
                </View>
              </View>

              <Text style={styles.chartLabel}>{item.label}</Text>

              <View style={styles.chartFooter}>
                <Text style={styles.chartFooterValue}>V {formatCurrency(item.vale)}</Text>
                <Text style={styles.chartFooterValue}>R {formatCurrency(item.refeicao)}</Text>
              </View>
            </View>
          );
        })}
      </ScrollView>
    </View>
  );
}

export default function IndexScreen() {
  const [dashboard, setDashboard] = useState<RevenueDashboard | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadData = async (mode: 'initial' | 'refresh' = 'initial') => {
    if (mode === 'refresh') {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    try {
      const data = await fetchRevenueDashboard();
      setDashboard(data);
      setError(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao carregar o faturamento.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  if (loading && !dashboard) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.centerState}>
          <ActivityIndicator size="large" color="#2563EB" />
          <Text style={styles.centerText}>Carregando faturamento...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (error && !dashboard) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.centerState}>
          <Text style={styles.errorTitle}>Nao foi possivel abrir o app</Text>
          <Text style={styles.errorText}>{error}</Text>
          <Pressable style={styles.retryButton} onPress={() => loadData()}>
            <Text style={styles.retryButtonText}>Tentar novamente</Text>
          </Pressable>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => loadData('refresh')} />}
      >
        <View style={styles.headerCard}>
          <Text style={styles.headerEyebrow}>PeC Faturamento</Text>
          <Text style={styles.headerTitle}>Relatorio sem login</Text>
          <Text style={styles.headerText}>
            Visualizacao direta do faturamento diario e mensal com dinheiro e cartao em destaque.
          </Text>
          {dashboard?.generated_at ? (
            <Text style={styles.headerTime}>
              Atualizado em {new Date(dashboard.generated_at).toLocaleString('pt-BR')}
            </Text>
          ) : null}
        </View>

        {dashboard ? (
          <>
            <SummarySection
              eyebrow="Hoje"
              title="Faturamento diario"
              summary={dashboard.daily}
              secondaryTitle="Vales em segundo plano"
            />

            <ChartSection
              title="Ultimos 7 dias"
              hint="Barras verdes representam dinheiro e barras azuis representam cartao."
              items={dashboard.charts.daily}
            />

            <SummarySection
              eyebrow="Mes atual"
              title="Faturamento mensal"
              summary={dashboard.monthly}
              secondaryTitle="Vales, refeicao e folha"
            />

            <ChartSection
              title="Ultimos 6 meses"
              hint="Comparativo de entradas principais com apoio de vale e refeicao."
              items={dashboard.charts.monthly}
            />
          </>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F5EFE4',
  },
  content: {
    padding: 18,
    gap: 16,
    paddingBottom: 36,
  },
  centerState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
    gap: 12,
    backgroundColor: '#F5EFE4',
  },
  centerText: {
    color: '#5B4636',
    fontSize: 15,
  },
  errorTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: '#7C2D12',
    textAlign: 'center',
  },
  errorText: {
    color: '#7C2D12',
    fontSize: 14,
    lineHeight: 20,
    textAlign: 'center',
  },
  retryButton: {
    marginTop: 6,
    borderRadius: 16,
    backgroundColor: '#2563EB',
    paddingHorizontal: 18,
    paddingVertical: 12,
  },
  retryButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '700',
  },
  headerCard: {
    borderRadius: 28,
    backgroundColor: '#1E293B',
    padding: 22,
    shadowColor: '#0F172A',
    shadowOpacity: 0.2,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 10 },
    elevation: 6,
  },
  headerEyebrow: {
    color: '#F59E0B',
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 2,
    textTransform: 'uppercase',
  },
  headerTitle: {
    marginTop: 8,
    color: '#F8FAFC',
    fontSize: 30,
    fontWeight: '800',
  },
  headerText: {
    marginTop: 8,
    color: '#CBD5E1',
    fontSize: 15,
    lineHeight: 22,
  },
  headerTime: {
    marginTop: 14,
    color: '#94A3B8',
    fontSize: 12,
  },
  sectionCard: {
    borderRadius: 26,
    backgroundColor: '#FFFCF6',
    borderWidth: 1,
    borderColor: '#E6D7BF',
    padding: 18,
    gap: 14,
  },
  sectionEyebrow: {
    color: '#B45309',
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 1.5,
    textTransform: 'uppercase',
  },
  sectionTitle: {
    color: '#1F2937',
    fontSize: 24,
    fontWeight: '800',
  },
  heroMetric: {
    borderRadius: 24,
    backgroundColor: '#EFF6FF',
    borderWidth: 1,
    borderColor: '#BFDBFE',
    padding: 18,
  },
  heroMetricLabel: {
    color: '#2563EB',
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 1.4,
    textTransform: 'uppercase',
  },
  heroMetricValue: {
    marginTop: 8,
    color: '#0F172A',
    fontSize: 30,
    fontWeight: '800',
  },
  heroMetricCaption: {
    marginTop: 6,
    color: '#475569',
    fontSize: 13,
  },
  metricGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  metricCard: {
    minWidth: '48%',
    flexGrow: 1,
    borderRadius: 20,
    padding: 16,
  },
  metricCardPrimary: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#D8E3F5',
  },
  metricCardSecondary: {
    backgroundColor: '#FCF4E8',
    borderWidth: 1,
    borderColor: '#F1D5AA',
  },
  metricLabel: {
    color: '#475569',
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 1,
    textTransform: 'uppercase',
  },
  metricLabelSecondary: {
    color: '#92400E',
  },
  metricValue: {
    marginTop: 10,
    fontSize: 24,
    fontWeight: '800',
  },
  secondaryGroupTitle: {
    color: '#6B7280',
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 1.2,
    textTransform: 'uppercase',
  },
  chartHint: {
    color: '#6B7280',
    fontSize: 14,
    lineHeight: 20,
  },
  chartScroll: {
    gap: 14,
    paddingTop: 6,
    paddingRight: 6,
  },
  chartColumn: {
    width: 78,
    alignItems: 'center',
    gap: 8,
  },
  chartBars: {
    height: 156,
    flexDirection: 'row',
    gap: 8,
    alignItems: 'flex-end',
  },
  chartTrack: {
    width: 24,
    height: 148,
    borderRadius: 16,
    backgroundColor: '#E5E7EB',
    justifyContent: 'flex-end',
    overflow: 'hidden',
  },
  chartFill: {
    width: '100%',
    borderRadius: 16,
  },
  chartLabel: {
    color: '#1F2937',
    fontSize: 12,
    fontWeight: '800',
  },
  chartFooter: {
    width: '100%',
    borderRadius: 14,
    backgroundColor: '#F3E8D6',
    paddingHorizontal: 8,
    paddingVertical: 6,
    gap: 3,
  },
  chartFooterValue: {
    color: '#7C2D12',
    fontSize: 10,
    fontWeight: '700',
  },
});
