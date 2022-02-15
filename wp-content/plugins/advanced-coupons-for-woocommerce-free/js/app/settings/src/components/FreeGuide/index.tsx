// #region [Imports] ===================================================================================================

// Libraries
import React from "react";
import { Tag, Button } from "antd";
import { BulbFilled } from "@ant-design/icons";

// CSS
import "./index.scss";

// #endregion [Imports]

// #region [Variables] ================================================================================================

declare var acfwAdminApp: any;

// #endregion [Variables]

// #region [Interfaces] ================================================================================================

interface IProps {
    className?: string;
    showSubtitle?: boolean;
    showTag?: boolean;
}

// #endregion [Interfaces]

// #region [Component] ================================================================================================

const FreeGuide = (props: IProps) => {

    const { className, showSubtitle, showTag } = props;

    const { free_guide: {
        tag,
        title,
        subtitle,
        content,
        image,
        button,
        list
    } } = acfwAdminApp;

    return (
        <div className={`coupons-free-guide ${ className }`}>
            <div className="inner">
                { showTag ? <Tag color="#1693A7">{ tag }</Tag> : null }
                <h2>{ title }</h2>
                <img src={ image } alt={ title } />
                { showSubtitle ? <h3>{ subtitle }</h3> : null }
                <p dangerouslySetInnerHTML={{ __html: content }} />
                <ul>
                    { list.map( (list_text: string, index: number) => (
                        <li key={ index }>
                            <BulbFilled />
                            { list_text }
                        </li>
                    ) ) }
                </ul>
                <p>
                    <Button
                        type="link"
                        className="cta"
                        href={ showSubtitle ? button.help_link : button.link }
                        target="_blank"
                        size="large"
                    >
                        { button.text }
                    </Button>
                </p>
            </div>
        </div>
    );
};

FreeGuide.defaultProps = {
    className: '',
    showSubtitle: false,
    showTag: true,
};

export default FreeGuide;

// #endregion [Component]