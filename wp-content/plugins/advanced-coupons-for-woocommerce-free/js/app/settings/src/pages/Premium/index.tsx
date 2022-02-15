// #region [Imports] ===================================================================================================

// Libraries
import React from "react";
import { Typography } from "antd";

// Styles
import "./index.scss";

// #endregion [Imports]

// #region [Variables] =================================================================================================

declare var acfwAdminApp: any;
const { Text, Link } = Typography;

// #endregion [Variables]

// #region [Component] =================================================================================================

const Premium = () => {

    const { premium_page: {
        image,
        title, 
        desc, 
        header, 
        rows, 
        action
    }, logo_alt } = acfwAdminApp;

    return (
        <div id="acfw-premium" className="acfwf-upgrade-settings-block">

            <p><img className="logo" src={ image } alt={ logo_alt } /></p>
            <h2 dangerouslySetInnerHTML={{ __html: title }} />
            <p><Text>{ desc }</Text></p>

            <div className="responsive-table">
                <table>
                    <thead>
                        <tr>
                            <th className="feature">
                                <Text>{ header.feature }</Text>
                            </th>
                            <th className="free">
                                <Text>{ header.free }</Text>
                            </th>
                            <th className="premium">
                                <Text>{ header.premium }</Text>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        { rows.map( ({feature, free, premium}: any, key: number) => (
                            <tr key={ key }>
                                <td className="feature">
                                    <Text>{ feature }</Text>
                                </td>
                                <td className="free dashicons-before dashicons-no">
                                    <Text>{ free }</Text>
                                </td>
                                <td className="premium dashicons-before dashicons-yes-alt">
                                    <Text>{ premium }</Text>
                                </td>
                            </tr>
                        ) ) }
                    </tbody>
                </table>
            </div>
            <div className="cta-block">
                <h3><Text>{ action.title }</Text></h3>
                <p>
                    <Link className="acfw-upgrade-button" href={ action.btn_link } target="_blank">{ action.btn_text }</Link>
                </p>
            </div>
        </div>
    );
}

export default Premium;


// #endregion [Component]