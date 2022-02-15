// #region [Imports] ===================================================================================================

// Libraries
import React from "react";
import { bindActionCreators, Dispatch } from "redux";
import { connect } from "react-redux";
import { Skeleton, Divider } from "antd";

// Types
import { IStore } from "../../types/store";
import { ISection } from "../../types/section";
import { ISettingValue } from "../../types/settings";

// Components
import SettingField from "./SettingField";
import FreeGuide from "../FreeGuide";
import UpsellProvider from "../UpsellProvider";

// #endregion [Imports]

// #region [Interfaces] ================================================================================================

interface IProps {
    sections: ISection[];
    settingValues: ISettingValue[]
    currentSection: string|null;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const SettingsForm = (props: IProps) => {

    const { sections, settingValues, currentSection } = props;

    const idx           = currentSection ? sections.findIndex(i => i.id === currentSection ) : 0;
    const sectionID     = sections.length ? sections[idx].id : '';
    const sectionFields = sections.length ? sections[idx].fields : [];

    if ( sectionFields.length < 1 || settingValues.length < 1 ) {
        return (
            <>
                <Skeleton loading={true} active paragraph={{ rows: 1 }} />
                <Divider />
                <Skeleton loading={true} active paragraph={{ rows: 2 }} title={false} />
                <Divider />
                <Skeleton loading={true} active paragraph={{ rows: 2 }} title={false} />
                <Divider />
                <Skeleton loading={true} active paragraph={{ rows: 2 }} title={false} />
            </>
        )
    }

    return (
        <UpsellProvider>
            <div className={`settings-form ${sectionID}-form`}>
                { sectionFields.map( field => <SettingField key={ field.id } field={ field } /> ) }

                { ! currentSection || "general_section" === currentSection ? (
                    <>
                        <Divider />
                        <FreeGuide />
                    </>
                ) : null }
            </div>
        </UpsellProvider>
    );
};

const mapStateToProps = (store: IStore) => ({ sections: store.sections, settingValues: store.settingValues });

const mapDispatchToProps = (dispatch: Dispatch) => ({
    actions: bindActionCreators({}, dispatch)
})

export default connect(mapStateToProps, mapDispatchToProps)(SettingsForm);

// #endregion [Component]