// #region [Imports] ===================================================================================================

// Libraries
import React from "react";
import { bindActionCreators, Dispatch } from "redux";
import { connect } from "react-redux";
import { Link, useHistory } from "react-router-dom";
import { Skeleton, Menu } from "antd";

// Actions
import { PageActions } from "../../store/actions/page";

// Types
import { IStore } from "../../types/store";
import { ISection } from "../../types/section";

// SCSS
import "./index.scss";

// Components
import MenuIcon from "./icons";

// Helpers
import { getPathPrefix } from "../../helpers/utils";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwAdminApp: any;
const { setStorePage } = PageActions;
const pathPrefix = getPathPrefix();

// #endregion [Variables]

// #region [Interfaces] ================================================================================================

interface IActions {
    setStorePage: typeof setStorePage;
}

interface IProps {
    sections: ISection[];
    currentSection: string|null;
    actions: IActions;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const SettingsNav = (props: IProps) => {

    const { sections , currentSection, actions } = props;
    const { app_pages } = acfwAdminApp;
    const defaultKey: string = currentSection ? currentSection : "general_section";
    const history = useHistory();

    if ( sections.length < 1 ) {
        return (
            <div className="settings-nav-skeleton">
                <Skeleton active paragraph={false} />
                <Skeleton active paragraph={false} />
                <Skeleton active paragraph={false} />
                <Skeleton active paragraph={false} />
                <Skeleton active paragraph={false} />
            </div>
        )
    }

    const handleMenuClick = (id: string) => {
        history.push(`${ pathPrefix }admin.php?page=${id}`);
        actions.setStorePage({ data: id });
    }

    const filteredSections = sections.filter( ({show}) => show );

    return (
        <Menu
            className="acfw-settings-nav"
            defaultSelectedKeys={[defaultKey]}
        >
            { filteredSections.map( ({ id , title }) => (
                <Menu.Item key={ id }>
                    <Link to={ `${ pathPrefix }admin.php?page=acfw-settings&section=${id}` }>
                        <MenuIcon section={ id } />
                        { title }
                    </Link>
                </Menu.Item>
            ) ) }

            { app_pages
              .filter( ({slug}: any) => slug !== 'acfw-settings' && slug !== 'acfw-about' )
              .map(({ slug, label, page }: any) => (
                <Menu.Item 
                    key={ page }
                    className={ page }
                >
                    <button 
                        className="buttonlink"
                        onClick={ () => handleMenuClick( slug ) }
                    >
                        <MenuIcon section={ page } />
                        { label }
                    </button>
                </Menu.Item>
            ) ) }

        </Menu>
    );
};

const mapStateToProps = (store: IStore) => ({ sections: store.sections });

const mapDispatchToProps = (dispatch: Dispatch) => ({
    actions: bindActionCreators({ setStorePage }, dispatch)
})

export default connect(mapStateToProps, mapDispatchToProps)(SettingsNav);

// #endregion [Component]