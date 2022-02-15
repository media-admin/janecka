// #region [Imports] ===================================================================================================

// Libraries
import React from "react";
import { useHistory } from "react-router-dom";
import { bindActionCreators, Dispatch } from "redux";
import { connect } from "react-redux";

// Actions
import { PageActions } from "../../store/actions/page";

// Types
import { IStore } from "../../types/store";

// Helpers
import { getPathPrefix } from "../../helpers/utils";

// SCSS
import "./index.scss";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwAdminApp: any;
const { setStorePage } = PageActions;

// #endregion [Variables]

// #region [Interfaces]=================================================================================================

interface IActions {
  setStorePage: typeof setStorePage;
}

interface IProps {
  page: string;
  actions: IActions;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const CouponsNav = (props: IProps) => {
  const { page, actions } = props;
  const history = useHistory();
  const pathPrefix = getPathPrefix();

  const {
    coupon_nav: { toplevel, links },
    app_pages,
  } = acfwAdminApp;

  const handleMenuClick = (id: string) => {
    history.push(`${pathPrefix}admin.php?page=${id}`);
    actions.setStorePage({ data: id });
  };

  return (
    <>
      <a
        href={`${pathPrefix}edit.php?post_type=shop_coupon`}
        className="wp-has-submenu wp-has-current-submenu wp-menu-open menu-top toplevel_page_acfw-admin"
        aria-haspopup="false"
      >
        <div className="wp-menu-arrow">
          <div></div>
        </div>
        <div className="wp-menu-image dashicons-before dashicons-tickets-alt">
          <br />
        </div>
        <div className="wp-menu-name">{toplevel}</div>
      </a>
      <ul className="wp-submenu wp-submenu-wrap">
        <li className="wp-submenu-head" aria-hidden="true">
          {toplevel}
        </li>
        {links.map(({ link, text }: any, key: number) => (
          <li key={key} className={key === 0 ? "wp-first-item" : ""}>
            <a href={link}>{text}</a>
          </li>
        ))}
        {app_pages.map(({ slug, label }: any) => (
          <li key={slug} className={slug === page ? `current` : ""}>
            <button
              className={`buttonlink ${slug}-link`}
              onClick={() => handleMenuClick(slug)}
            >
              {label}
            </button>
          </li>
        ))}
      </ul>
    </>
  );
};

const mapStateToProps = (store: IStore) => ({ page: store.page });

const mapDispatchToProps = (dispatch: Dispatch) => ({
  actions: bindActionCreators({ setStorePage }, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(CouponsNav);

// #endregion [Component]
