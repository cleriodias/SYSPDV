import type { RevenueDashboard } from '../types';

const API_BASE_URL = 'https://paoecafe83.com.br/app/endpoints';

async function parseResponse(response: Response) {
  const data = (await response.json().catch(() => null)) as RevenueDashboard | { message?: string } | null;

  if (!response.ok || !data) {
    const message = response.status >= 502
      ? 'Servidor de faturamento indisponivel no momento.'
      : response.status === 404
        ? 'Endpoint de faturamento ainda nao publicado.'
        : (data && 'message' in data && typeof data.message === 'string' && data.message) ||
          'Falha ao carregar o faturamento.';
    throw new Error(message);
  }

  return data as RevenueDashboard;
}

export async function fetchRevenueDashboard(): Promise<RevenueDashboard> {
  const response = await fetch(`${API_BASE_URL}/mobile/revenue/dashboard`, {
    headers: {
      Accept: 'application/json',
    },
  });

  return parseResponse(response);
}
