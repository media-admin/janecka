// #region [Imports] ===================================================================================================

import {useContext} from "@wordpress/element";
import {RoutingContext} from "./Router";

// #endregion [Imports]

// #region [Interfaces] ================================================================================================

interface IRoute {
  key: string;
  path: string|string[];
  render: any;
}

// #endregion [Interfaces]

// #region [Component] =================================================================================================

const Route = (props: IRoute) => {
  const {path, render} = props;
  const routerPath = useContext(RoutingContext);

  // handle if path is string
  if ("string" === typeof path && path !== routerPath.path)
    return null;

  // handle if path is array
  if(Array.isArray(path) && path.findIndex(p => p === routerPath.path) < 0)
    return null;

  return render();
}

export default Route;

// #endregion [Component]