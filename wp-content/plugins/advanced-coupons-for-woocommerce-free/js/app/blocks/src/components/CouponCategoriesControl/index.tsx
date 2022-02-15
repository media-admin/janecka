// #region [Imports] ===================================================================================================

// Libraries
import {useEffect, useState} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import {SelectControl, CheckboxControl, Spinner} from "@wordpress/components";

// Types
import {ICategory} from "../../types/category";

// Custom Hooks
import useIsMounted from "../../hooks/ismounted";

// SCSS
import "./index.scss";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwfBlocksi18n: any;
const {couponsCategoryTexts} = acfwfBlocksi18n;

// #endregion [Variables]

// #region [Interfaces] ================================================================================================

interface IProps {
  categories: ICategory[];
  setCategories: (categories: any) => void;
  selected: number[];
  onChange: (ids: number[]) => void;
}

// #region [Component] =================================================================================================

const CouponCategoriesControl = (props: IProps) => {
  const {categories, setCategories, selected, onChange} = props;
  const isMounted = useIsMounted();

  useEffect(() => {
    if (0 < categories.length) return;
    apiFetch({path: '/wp/v2/shop_coupon_cat?per_page=-1&order=desc&orderby=count'})
      .then(categories => {
        if (isMounted) {
          setCategories(categories);
        }
      });
  }, []);

  const handleCheckboxChange = (isChecked: boolean, cat: ICategory) => {
    if (isChecked) 
      onChange([...selected,  cat.id]);
    else
      onChange(selected.filter(id => id !== cat.id));
  };

  return (
    <div className="acfw-coupon-categories">
      <div className="acfw-coupon-categories__label">
        {couponsCategoryTexts.selectCategories}
      </div>
      <div className="acfw-coupon-categories__list">
        {categories.length ? (
          <>
            {categories.map(cat => (
              <div className="acfw-coupon-categories__item" key={cat.id}>
                <CheckboxControl 
                  label={`${cat.name} (${cat.count})`}
                  checked={selected.includes(cat.id)}
                  onChange={(isChecked: boolean) => handleCheckboxChange(isChecked, cat)}
                />
              </div>
            ))}
          </>
        ) : (
          <div className="acfw-coupon-categories__spinner">
            <Spinner />
          </div>
        )}
      </div>
    </div>
  );
};

export default CouponCategoriesControl;

// #endregion [Component]
