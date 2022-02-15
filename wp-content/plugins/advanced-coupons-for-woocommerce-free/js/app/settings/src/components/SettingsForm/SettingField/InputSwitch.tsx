// #region [Imports] ===================================================================================================

// Libraries
import React, { useState } from "react";
import { bindActionCreators, Dispatch } from "redux";
import { connect } from "react-redux";
import { Input, Switch, Select, message } from "antd";

// Components
import PremiumModule from "./PremiumModule";

// Types
import { IStore } from "../../../types/store";
import { ISectionField } from "../../../types/section";

// Actions
import { SettingActions } from "../../../store/actions/setting";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwAdminApp: any;
declare var acfwpElements: any;

const { updateSetting, setStoreSettingItem } = SettingActions;
const { action_notices } = acfwAdminApp;

// #endregion [Variables]

// #region [Interfaces] ================================================================================================

interface IActions {
    updateSetting: typeof updateSetting;
    setStoreSettingItem: typeof setStoreSettingItem;
}

interface IProps {
    field: ISectionField;
    setShowSpinner: any;
    validateInput: any;
    value: any;
    actions: IActions;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const InputSwitch = (props: IProps) => {

    const { field, setShowSpinner, validateInput, value: savedValue, actions } = props;
    const { id, type, placeholder, default: defaultValue } = field;
    const [saveTimeout, setSaveTimeout]: [any, any] = useState(null);
    const value = savedValue !== undefined && savedValue !== false ? savedValue : defaultValue;

    const handleValueChange = (inputValue: unknown, needTimeout: boolean = false ) => {

        const updateValue = () => {

            // validate value
            if ( ! validateInput( inputValue ) ) return;

            // set state early to prevent rerenders.
            actions.setStoreSettingItem({ data: {id: id, value: inputValue} });

            // update setting value via api
            actions.updateSetting({ 
                data: {id: id, value: inputValue, type: type},
                processingCB: () => setShowSpinner(true),
                successCB: () => {
                    message.success(<><strong>{field.title}</strong> {action_notices.success}</>);
                    setShowSpinner(false);
                },
                failCB: () => {
                    message.error(<><strong>{field.title}</strong> {action_notices.fail}</>);
                    setShowSpinner(false);
                },
            });
        };

        // we add timeout for fields that requires users to update value by typing.
        if ( needTimeout ) {

            // clear timeout when user is still editing
            if ( saveTimeout ) {
                clearTimeout( saveTimeout );
                setSaveTimeout( null );
            }

            // set 1 second delay before updating value.
            setSaveTimeout( setTimeout( updateValue , 1000 ) );

        } else
            updateValue();        
    }
    
    if ( "checkbox" === type || "module" === type ) {
        return <Switch 
            key={ id }
            checked={ value === "yes" } 
            defaultChecked={ value === "yes" } 
            onChange={ inputValue => handleValueChange(inputValue ? "yes" : "") } 
        />;
    }

    if ( "premiummodule" === type) {
        return <PremiumModule field={field} />
    }

    if ( "textarea" === type ) {
        return <Input.TextArea 
            key={ id }
            rows={3} 
            placeholder={ placeholder } 
            defaultValue={ value } 
            onChange={ (event) => handleValueChange( event.target.value , true ) }
        />;
    }

    if ( "select" === type ) {
        const { options } = field;
        return (
            <Select 
                key={ id } 
                defaultValue={ value.toString() }
                style={ { width: `50%` } }
                placeholder={ placeholder } 
                onSelect={ inputvalue => handleValueChange( inputvalue ) }
            >
                { options ? options.map( ({key, label}) => <Select.Option key={ key.toString() } value={ key.toString() }>{ label }</Select.Option> ) : null }
            </Select>
        );
    }

    if ( [ "text", "url", "number" ].indexOf( type ) > -1 )
        return <Input 
            key={ id }
            type={ type } 
            name={ id } 
            placeholder={ placeholder } 
            defaultValue={ value } 
            onChange={ event => handleValueChange( event.target.value, true ) }
        />;

    if ( "price" === type )
        return <Input 
                type="text" 
                className="wc_input_price"
                name={ id } 
                placeholder={ placeholder } 
                defaultValue={ value } 
                onChange={ (event: any) => handleValueChange( event.target.value, true ) }
            />;
   
    if ("acfwflicense" === type) {
        const {licenseContent} = field;
        return (
            <div className="acfw-license-field">
                {licenseContent?.map((text) => <p dangerouslySetInnerHTML={{__html: text}} />)}
            </div>
        );
    }

    return null;
};

const mapStateToProps = (store: IStore, props: any) => {

    const { id } = props.field;
    const index = store.settingValues.findIndex((i: any) => i.id === id );
    const value = index > -1 ? store.settingValues[index].value : '';
    
    return { value: value };
};

const mapDispatchToProps = (dispatch: Dispatch) => ({
    actions: bindActionCreators({ updateSetting, setStoreSettingItem }, dispatch)
})

export default connect(mapStateToProps, mapDispatchToProps)(InputSwitch);

// #endregion [Component]