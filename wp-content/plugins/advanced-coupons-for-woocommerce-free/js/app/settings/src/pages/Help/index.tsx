// #region [Imports] ===================================================================================================

// Libraries
import React from "react";
import { Row, Col, Card, Button } from "antd";

// CSS
import "./index.scss";

// Components
import Logo from "../../components/Logo";
import FreeGuide from "../../components/FreeGuide";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwAdminApp: any;
declare var acfwpElements: any;

// #endregion [Variables]

// #region [Interfaces]=================================================================================================

interface IHelpCard {
    title: string;
    content: string;
    action: IHelpCardAction
}

interface IHelpCardAction {
    link: string;
    text: string;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const Help = () => {

    const { help_page: {
        title,
        desc,
        cards
    }} = acfwAdminApp;

    const { HelpPremium } = acfwpElements;

    return (
        <div className="help-page">
            <div className="page-header help-header">
                <Logo />
                <h1>{ title }</h1>
                <p>{ desc }</p>
            </div>
            <Row gutter={10}>
                { cards.map( (card: IHelpCard, key: number) => (
                    <Col key={key} className="acfw-border-box" span={12}>
                        <Card>
                        <h2>{ card.title }</h2>
                        <p>{ card.content }</p>
                        <Button 
                            type="primary" 
                            href={ card.action.link }
                            size="large"
                            target="_blank"
                        >
                            { card.action.text }
                        </Button>
                        </Card>
                    </Col>
                ) ) }
            </Row>
            <Row gutter={10}>
                <Col className="acfw-border-box" span={24}>
                    <FreeGuide className="help-guide" showSubtitle={true} showTag={false} />
                </Col>
            </Row>
            { HelpPremium ? <HelpPremium /> : null }
        </div>
    );
};

export default Help;

// #endregion [Component]