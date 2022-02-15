// #region [Imports] ===================================================================================================

// Libraries
import {useState} from "@wordpress/element";
import ServerSideRender from '@wordpress/server-side-render';
// @ts-ignore
import {clamp} from "lodash";
import {
  Button,
  Placeholder, 
  PanelBody,
  SelectControl,
  RangeControl, 
  ToolbarGroup, 
  withSpokenMessages
} from "@wordpress/components";
// @ts-ignore
import { BlockControls, InspectorControls } from "@wordpress/block-editor";

// Components
import ContentSettingsControl from "../../components/ContentSettingsControl";

// Utils
import {layoutDefaults, orderByOptions} from "../../utils/sharedAtts";

// Types
import {IAttributes} from "../../types/settings";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwfBlocksi18n: any;
const {
  isPremium,
  couponsCustomerTexts, 
  displayTypeFieldTexts, 
  orderTypeFieldTexts, 
  numberofItemsFieldLabel, 
  numberofColumnsFieldLabel, 
  contentDisplaySettings,
  premiumUpsellMessage,
  doneBtnText
} = acfwfBlocksi18n;

// #endregion [Variables]

// #region [Interfaces] ================================================================================================

interface IProps {
  attributes: IAttributes;
  name: string;
  setAttributes: (IAttributes) => void;
  debouncedSpeak: (string) => void;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const CouponsByCustomerBlock = (props: IProps) => {
  const {attributes, name, setAttributes, debouncedSpeak} = props;
  const [isEditing, setIsEditing]: [boolean, any] = useState('' === attributes.display_type);

  const displayTypeOptions = [
    {value: 'both', label: displayTypeFieldTexts.options.couponsAndVirtualCoupons},
    {value: 'coupons_only', label: displayTypeFieldTexts.options.couponsOnly},
    {value: 'virtual_only', label: displayTypeFieldTexts.options.virtualCouponsOnly},
  ];

  const handleDone = () => {
    if ('' === attributes.display_type) {
      setAttributes({display_type: 'both'});
    }

    setIsEditing(false);
  };

  return (
    <>
      <BlockControls>
        <ToolbarGroup
          controls={ [
            {
              icon: 'edit',
              title: 'Edit',
              onClick: () => setIsEditing(!isEditing),
              isActive: isEditing,
            },
          ] }
        />
      </BlockControls>

      <InspectorControls key="inspector">
        <PanelBody
          title={contentDisplaySettings.title}
          initialOpen
        >
          <ContentSettingsControl
            settings={attributes.contentVisibility}
            onChange={ (value) => setAttributes( { contentVisibility: value }) }
          />
        </PanelBody>
      </InspectorControls>

      {isEditing ? (
        <Placeholder
          label={couponsCustomerTexts.title}
          className={`acfw-block-coupons-grid acfw-block-coupons-customer ${!isPremium ? 'acfw-disabled-upsell': ''}`}
        >
          <div className="description fullwidth-field">{couponsCustomerTexts.description}</div>
          <div className="display-type__selection one-half-col">
            <SelectControl
              label={displayTypeFieldTexts.label}
              value={attributes.display_type}
              options={displayTypeOptions}
              onChange={(value) => setAttributes({display_type: value})}
            />
          </div>

          <div className="order-by__selection one-half-col">
            <SelectControl
              label={orderTypeFieldTexts.label}
              value={attributes.order_by}
              options={orderByOptions}
              onChange={(value) => setAttributes({order_by: value})}
            />
          </div>
          <div className="items-count__range one-third-col">
            <RangeControl
              label={numberofItemsFieldLabel}
              value={attributes.count}
              onChange={(value) => {
                const newValue = clamp(value, layoutDefaults.minCount, layoutDefaults.maxCount);
                setAttributes({
                  count: newValue
                });
              }}
              min={layoutDefaults.minCount}
              max={layoutDefaults.maxCount}
            />
          </div>
          <div className="column-count__range one-third-col">
            <RangeControl
              label={numberofColumnsFieldLabel}
              value={attributes.columns}
              onChange={(value) => {
                const newValue = clamp(value, layoutDefaults.minColumns, layoutDefaults.maxColumns);
                setAttributes({
                  columns: newValue
                });
              }}
              min={layoutDefaults.minColumns}
              max={layoutDefaults.maxColumns}
            />
          </div>
          
          <div className="block-actions fullwidth-field">
            <Button
              isPrimary
              onClick={handleDone}
            >
              {doneBtnText}
            </Button>
          </div>
          
          {!isPremium && (
            <div className="acfw-upsell-message" 
              dangerouslySetInnerHTML={
                { __html: premiumUpsellMessage}
              } 
            />
          )}
        </Placeholder>
      ) : (
        <ServerSideRender 
          block={name}
          attributes={attributes}
          EmptyResponsePlaceholder={() => (
            <Placeholder
              label={couponsCustomerTexts.title}
              className="acfw-block-coupons-grid acfw-block-coupons-customer"
            >
              {couponsCustomerTexts.emptyDesc}

              {!isPremium && (
                <div className="acfw-upsell-message" 
                  dangerouslySetInnerHTML={
                    { __html: premiumUpsellMessage}
                  } 
                />
              )}
            </Placeholder>
          )}
        />
      )}
    </>
  );
};

export default withSpokenMessages(CouponsByCustomerBlock);