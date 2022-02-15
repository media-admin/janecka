// #region [Imports] ===================================================================================================

// Libraries
import {useState} from "@wordpress/element";
import ServerSideRender from '@wordpress/server-side-render';
import {
  Button,
  Placeholder,
  PanelBody,
  ToolbarGroup,
  withSpokenMessages
} from "@wordpress/components";
// @ts-ignore
import { BlockControls, InspectorControls } from "@wordpress/block-editor";

// Components
import ContentSettingsControl from "../../components/ContentSettingsControl";
import CouponSearchControl from "../../components/CouponSearchControl";

// Types
import {IContentVisibility} from "../../types/settings";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwfBlocksi18n: any;
const {
  singleCouponTexts, 
  contentDisplaySettings, 
  currentlySelectedCouponLabel,
  doneBtnText
} = acfwfBlocksi18n;

// #endregion [Variables]

// #region [Interfaces] ================================================================================================

interface IAttributes {
  coupon_id: number;
  coupon_code: string;
  contentVisibility: IContentVisibility;
}

interface IProps {
  attributes: IAttributes;
  name: string;
  setAttributes: (IAttributes) => void;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const SingleCouponBlock = (props: IProps) => {
  const {attributes, name, setAttributes} = props;
  const [isEditing, setIsEditing]: [boolean, any] = useState(1 > attributes.coupon_id);

  const setCouponId = (couponId: number) => {
    setAttributes({coupon_id: couponId});
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
              isActive: isEditing
            }
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
          label={singleCouponTexts.title}
          className="acfw-block-single-coupon"
        >
          {singleCouponTexts.description}
          <div className="acfw-single-block-fields">
            {'' !== attributes.coupon_code && (
              <div className="acfw-block-current-coupon-selected">
                <span>{currentlySelectedCouponLabel}</span>
                <span className="coupon-code" title={attributes.coupon_code}>
                  {attributes.coupon_code}
                </span>
              </div>
            )}
            <div className="acfw-block-coupon__selection">
              <CouponSearchControl
                coupon_id={attributes.coupon_id}
                setAttributes={setAttributes}
              />
            </div>
          </div>
          <div className="acfw-block-action-buttons">
          <Button
            isPrimary
            onClick={() => setIsEditing(false)}
          >
            {doneBtnText}
          </Button>
          </div>
        </Placeholder>
      ) : (
        <>
          {0 < attributes.coupon_id ? (
            <ServerSideRender
              block={name}
              attributes={attributes}
              EmptyResponsePlaceholder={() => (
                <Placeholder
                  label={singleCouponTexts.title}
                  className="acfw-block-single-coupon"
                >
                  {singleCouponTexts.emptyDesc}
                </Placeholder>
              )}
            />
          ) : (
            <Placeholder
              label={singleCouponTexts.title}
              className="acfw-block-coupons-grid acfw-block-coupons-category"
            >
              <p>{singleCouponTexts.selectDesc}</p>
            </Placeholder> 
          )}
        </>
      )}
    </>
  );
}

export default withSpokenMessages(SingleCouponBlock);

// #endregion [Component]
