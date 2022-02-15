declare var acfwfBlocksi18n: any;
const { orderTypeFieldTexts } = acfwfBlocksi18n;

export const layoutDefaults = {
  minColumns: 1,
  maxColumns: 6,
  minCount: 1,
  maxCount: 100,
};

export const orderByOptions = [
  { value: "date/desc", label: orderTypeFieldTexts.options.newestToOldest },
  { value: "date/asc", label: orderTypeFieldTexts.options.oldestToNewest },
  { value: "title/asc", label: orderTypeFieldTexts.options.aToZ },
  { value: "title/desc", label: orderTypeFieldTexts.options.zToA },
  { value: "expire/asc", label: orderTypeFieldTexts.options.earliestToExpire },
];

export const layoutAtts = {
  order_by: {
    type: "string",
    default: "date/desc",
  },

  columns: {
    type: "number",
    default: 3,
  },

  count: {
    type: "number",
    default: 10,
  },
};

const sharedAtts = {
  contentVisibility: {
    type: "object",
    default: {
      discount_value: true,
      description: true,
      usage_limit: true,
      schedule: true,
    },
  },

  isPreview: {
    type: "boolean",
    default: false,
  },
};

export default sharedAtts;
