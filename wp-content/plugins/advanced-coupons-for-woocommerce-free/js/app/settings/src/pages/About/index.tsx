// #region [Imports] ===================================================================================================

// Libraries
import React from "react";
import { Row, Col, Card, Button, Avatar } from "antd";

// CSS
import "./index.scss";

// #region [Variables] =================================================================================================

declare var acfwAdminApp: any;

// #endregion [Variables]

// #region [Interfaces]=================================================================================================

interface IAboutCard {
    icon: string;
    title: string;
    content: string;
    action: IAboutAction;
}

interface IAboutAction {
    status: string;
    link: string;
    external: boolean
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const About = () => {

    const { about_page : {
        title,
        desc,
        main_card,
        cards,
        status,
        status_texts,
        button_texts
    } } = acfwAdminApp;

    return (
        <div className="about-page">
            <div className="page-header about-header">
                <img className="acfw-logo" src={ acfwAdminApp.logo } alt="acfw logo" />
                <h1>{ title }</h1>
                <p>{ desc }</p>
            </div>
            <Row className="about-rymera-team">
                <Col className="content" span={12}>
                    <div className="inner">
                        <h2>{ main_card.title }</h2>
                        { main_card.content.map( (text: string, key: number) => <p key={ key }>{ text }</p> ) }
                    </div>
                </Col>
                <Col className="photo" span={12}>
                    <img src={ main_card.image } alt="Rymera Team" />
                </Col>
            </Row>
            <Row className="plugin-cards" gutter={10}>
                { cards.map( (card: IAboutCard, key: number) => (
                    <Col  key={ key } span={12}>
                        <Card
                            actions={[
                                (
                                    <>
                                        <strong>{ status }: </strong> 
                                        <span>{ status_texts[ card.action.status ] }</span>
                                    </>
                                ),
                                card.action.link ? (
                                    <Button 
                                        type="primary" 
                                        href={ card.action.link } 
                                        target={ card.action.external ? `_blank` : undefined }
                                    >
                                        { button_texts[ card.action.status ] }
                                    </Button>
                                ) : null
                            ]}
                        >
                            <Card.Meta 
                                avatar={ <Avatar src={ card.icon } /> }
                                title={ card.title }
                                description={ card.content }
                            />
                        </Card>
                    </Col>
                ) ) }
            </Row>
        </div>
    );
}

export default About;

// #endregion [Component]