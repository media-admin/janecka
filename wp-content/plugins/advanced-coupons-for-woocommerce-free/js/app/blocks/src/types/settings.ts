export interface IContentVisibility {
  discount_value: true;
  description: true;
  usage_limit: true;
  schedule: true;
}

export interface IAttributes {
  categories?: number[];
  order_by?: string;
  columns?: number;
  count?: number;
  contentVisibility?: IContentVisibility;
  display_type?: string;
}
