export type RevenueMetric = {
  key: string;
  label: string;
  total: number;
};

export type RevenueSummary = {
  period: {
    start: string;
    end: string;
    label: string;
  };
  entries_total: number;
  highlights: RevenueMetric[];
  secondary: RevenueMetric[];
};

export type RevenueChartPoint = {
  label: string;
  dinheiro: number;
  cartao: number;
  vale: number;
  refeicao: number;
};

export type RevenueDashboard = {
  generated_at: string;
  daily: RevenueSummary;
  monthly: RevenueSummary;
  charts: {
    daily: RevenueChartPoint[];
    monthly: RevenueChartPoint[];
  };
};
