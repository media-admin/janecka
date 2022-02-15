// #region [Imports] ===================================================================================================

// Libraries
import React, { useEffect } from "react";
import { useLocation } from "react-router-dom";
import { bindActionCreators, Dispatch } from "redux";
import { connect } from "react-redux";
import { Row, Col } from "antd";

// Types
import { IStore } from "../../types/store";
import { ISection } from "../../types/section";

// Actions
import { SectionActions } from "../../store/actions/section";

// Components
import SettingsNav from "../../components/SettingsNav";
import SettingsForm from "../../components/SettingsForm";
import Logo from "../../components/Logo";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwAdminApp: any;

const { readSections, readSection } = SectionActions;

// #endregion [Variables]

// #region [Interfaces]=================================================================================================

interface IActions {
    readSections: typeof readSections;
    readSection: typeof readSection;
}

interface IProps {
    sections: ISection[];
    actions: IActions;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const Settings = (props: IProps) => {

    const { sections , actions } = props;

    const urlParams = new URLSearchParams(useLocation().search);
    const currentSection = urlParams.get("section");

    // fetch all sections on first load.
    useEffect(() => {

        if ( sections.length > 0 ) return;

        actions.readSections({ id: currentSection });

    }, [actions]);

    // fetch fields of current section.
    useEffect(() => {

        if ( sections.length < 1 ) return;

        const idx = currentSection ? sections.findIndex(i => i.id === currentSection ) : 0;

        if ( sections[idx].fields.length < 1 )
            actions.readSection({ id: currentSection });

    }, [sections, actions, currentSection]);

    return (
    <>
        <div className="page-header settings-header">
            <Logo />
            <h1>{ acfwAdminApp.title }</h1>
            <p>{ acfwAdminApp.desc }</p>
        </div>
        <Row className="settings-content">
            <Col span={6}>
                <SettingsNav currentSection={ currentSection } />
            </Col>
            <Col span={18} className="content-column">
                <SettingsForm currentSection={ currentSection } />
            </Col>
        </Row>
    </>
    );
};

const mapStateToProps = (store: IStore) => ({ sections: store.sections });

const mapDispatchToProps = (dispatch: Dispatch) => ({
    actions: bindActionCreators({ readSections , readSection }, dispatch)
})

export default connect(mapStateToProps, mapDispatchToProps)(Settings);

// #endregion [Component]