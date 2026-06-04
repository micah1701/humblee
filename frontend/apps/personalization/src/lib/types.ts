export interface Persona {
  id: number;
  name: string;
  description: string;
  active: number;
  criteria: string;
  priority: number;
}

export interface Role {
  id: number;
  name: string;
}

export type CriterionType = 'i18n' | 'session_key' | 'required_role';

export interface Criterion {
  type: CriterionType;
  operator: string;
  value: string;
}

export type CriteriaGroup = Criterion[];
export type Criteria = CriteriaGroup[];
