
const defaults = {
	itemTotalTypes: [],
	itemMetaTypes: [],
	itemTableBlockTypes: [],
	discountTotalTypes: {}
};

// @ts-ignore sabSettings is window global
const globalSharedSettings = typeof sabSettings === 'object' ? sabSettings : {};

// Use defaults or global settings, depending on what is set.
const allSettings = {
	...defaults,
	...globalSharedSettings,
};

export { allSettings };
