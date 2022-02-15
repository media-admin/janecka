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
import CouponCategoriesControl from "../../components/CouponCategoriesControl";
import ContentSettingsControl from "../../components/ContentSettingsControl";

// Utils
import {layoutDefaults, orderByOptions} from "../../utils/sharedAtts";

// Types
import {IAttributes} from "../../types/settings";
import {ICategory} from "../../types/category";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwfBlocksi18n: any;
const {
  couponsCategoryTexts,
  orderTypeFieldTexts, 
  numberofItemsFieldLabel, 
  numberofColumnsFieldLabel, 
  contentDisplaySettings,
  doneBtnText,
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

const CouponsByCategoryBlock = (props: IProps) => {
  const {attributes, name, setAttributes, debouncedSpeak} = props;
  const [isEditing, setIsEditing]: [boolean, any] = useState(1 > attributes.categories.length);
  const [categories, setCategories]: [ICategory[], any] = useState([]);
  const hasCategories = attributes.categories.length > 0;

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
          label={couponsCategoryTexts.title}
          className="acfw-block-coupons-grid acfw-block-coupons-category"
        >
          {couponsCategoryTexts.description}
          <div className="wc-block-coupon-category__selection fullwidth-field">
            <CouponCategoriesControl
              categories={categories}
              setCategories={setCategories}
              selected={attributes.categories}
              onChange={(ids = []) => {
                setAttributes({categories: ids});
              }}
            />
          </div>
          <div className="order-by__selection one-third-col">
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
          <Button
            isPrimary
            onClick={() => setIsEditing(false)}
          >
            {doneBtnText}
          </Button>
        </Placeholder>

      ) : (
        <>
          {hasCategories ? (
            <ServerSideRender 
              block={name}
              attributes={attributes}
              EmptyResponsePlaceholder={() => (
                <Placeholder
                  label={couponsCategoryTexts.title}
                  className="acfw-block-coupons-grid acfw-block-coupons-category"
                >
                  {couponsCategoryTexts.emptyDesc}
                </Placeholder>
              )}
            />
          ) : (
            <Placeholder
              label={couponsCategoryTexts.title}
              className="acfw-block-coupons-grid acfw-block-coupons-category"
            >
              <p>{couponsCategoryTexts.selectDesc}</p>
            </Placeholder>
          )}
        </>
      )}

    </>
  );
}

export default withSpokenMessages(CouponsByCategoryBlock);

// #endregion [Component]