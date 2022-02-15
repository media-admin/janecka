// #region [Imports] ===================================================================================================

// Libraries
import {useEffect, useState} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { ComboboxControl, Spinner } from '@wordpress/components';

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwfBlocksi18n: any;
const {
  searchAndSelectCouponLabel
} = acfwfBlocksi18n;

// #endregion [Variables]

// #region [Interfaces] ================================================================================================

interface IProps {
  coupon_id: number;
  setAttributes: (attributes: any) => void;
}

interface IOption {
  value: number;
  label: string;
}

interface ICoupon {
  id: number;
  code: string;
}

// #region [Component] =================================================================================================

const CouponSearchControl = (props: IProps) => {
  const {coupon_id, setAttributes} = props;
  const [options, setOptions]: [IOption[], any] = useState([]);
  const [lastSearch, setLastSearch]: [string, any] = useState('');
  const [searchTimeout, setSearchTimeout]: [any, any] = useState(null);
  const [loading, setLoading] = useState(false);
  const [isEmptyResults, setIsEmptyResults] = useState(false);

  const handleCouponSearch = (search) => {

    setIsEmptyResults(false);

    // if user still typing then clear timeout.
    if (searchTimeout) {
      clearTimeout(searchTimeout);
      setSearchTimeout(null);
    }

    // clear data when search input is emptied.
    if (!search) {
      setLoading(false);
    }

    // only start search after three characters.
    if (search.length < 3) return;

    if ( ! loading ) setLoading( true );

    setSearchTimeout(
      setTimeout( async () => {

        try {

          // clear options value if last search value is not the same with current search.
          if (lastSearch && lastSearch !== search) {
            setOptions([]);
          }

          // fetch coupons data by provided search term.
          const coupons: ICoupon[] = await apiFetch({path: `/wc/v3/coupons/?search=${search}`});

          // map value and label of coupons as options.
          if (coupons.length) {
            setOptions(coupons.map(c => ({value: c.id, label: c.code})));
          } else {
            setIsEmptyResults(true);
          }

          setLoading(false);

        } catch (error) {
          console.log(error);
        }

      }, 1000)
    );

  };

  const handleSelectCoupon = (coupon_id) => {
    const index = options.findIndex(o => o.value === coupon_id);
    if (0 <= index) {
      setAttributes({
        coupon_id: coupon_id,
        coupon_code: options[index].label
      });
    }
  };

  return (
    <div className="acfw-coupon-search-field-wrap">
      <ComboboxControl
        label={searchAndSelectCouponLabel}
        value={coupon_id}
        onChange={handleSelectCoupon}
        options={options}
        onFilterValueChange={handleCouponSearch}
      />
      {loading && <Spinner />}
      {isEmptyResults && <div>{acfwfBlocksi18n.emptyCouponSearch}</div>}
    </div>
  );
};

export default CouponSearchControl;

// #endregion [Component]
