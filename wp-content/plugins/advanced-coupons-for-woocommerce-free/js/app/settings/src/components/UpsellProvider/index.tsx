// #region [Imports] ===================================================================================================

// Libraries
import React, {createContext, useState} from "react";
import {useHistory} from "react-router-dom";
import { Modal, Button } from "antd";
import {LockFilled} from "@ant-design/icons";

// Helpers
import { getPathPrefix } from "../../helpers/utils";

// SCSS
import "./index.scss";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwAdminApp: any;

// #endregion [Variables]

// #region [Interfaces] ================================================================================================

interface IModalData {
  title: string;
  content: string[];
  buttonTxt: string;
}

interface IModalProps {
  data: IModalData;
  showModal: boolean;
  setShowModal: any;
}

interface IProps {
  children: any;
}

// #endregion [Interfaces]

// #region [Contexts] =================================================================================================

export const UpsellContext = createContext({showModal: false, setShowModal: (value: boolean) => {}});

// #endregion [Contexts]

// #region [Component] =================================================================================================

const UpsellModal = (props: IModalProps) => {
  const {data: {title, content, buttonTxt}, showModal, setShowModal} = props;
  const history = useHistory();
  const pathPrefix = getPathPrefix();

  const redirectToPremium = () => {
    history.push(`${ pathPrefix }admin.php?page=acfw-premium`);
  };

  return (
    <Modal 
      className="acfw-upsell-modal"
      centered
      width={600}
      visible={showModal}
      onCancel={() => setShowModal(false)}
      footer={null}
    >
      <LockFilled />
      <h2>{title}</h2>
      {content.map((text: string) => <p>{text}</p>)}
      <Button type="primary" onClick={redirectToPremium}>{buttonTxt}</Button>
    </Modal>
  );
}

const UpsellProvider = (props: IProps) => {

  const {children} = props;
  const [showModal, setShowModal]: [boolean, any] = useState(false);

  return (
    <UpsellContext.Provider value={{showModal,setShowModal}}>
      {children}
      {acfwAdminApp?.upsellModal ? 
        <UpsellModal 
          data={acfwAdminApp?.upsellModal} 
          showModal={showModal} 
          setShowModal={setShowModal} 
        />
      : null}
    </UpsellContext.Provider>
  );
}

export default UpsellProvider;

// #endregion [Component]
