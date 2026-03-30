import type { AxiosResponse } from 'axios';
import { request } from '../request';

export type IfosQueryValue = string | number | boolean | null | undefined;

export interface IfosCollectionMeta {
  current_page?: number;
  per_page?: number;
  total?: number;
  last_page?: number;
}

type IfosCollectionResponse<T> = App.Service.Response<T> & {
  meta?: IfosCollectionMeta;
};

function extractCollectionMeta<T>(response?: AxiosResponse<IfosCollectionResponse<T>> | null) {
  const meta = response?.data?.meta;

  if (!meta || typeof meta !== 'object') {
    return null;
  }

  return meta;
}

export async function fetchIfosCollection<T extends Record<string, unknown> = Record<string, unknown>>(
  endpoint: string,
  params: Record<string, IfosQueryValue> = {}
) {
  const result = await request<T[]>({
    url: endpoint,
    params
  });

  return {
    ...result,
    meta: extractCollectionMeta(result.response)
  };
}

export function fetchIfosPayload(endpoint: string, params: Record<string, IfosQueryValue> = {}) {
  return request<unknown>({
    url: endpoint,
    params
  });
}
