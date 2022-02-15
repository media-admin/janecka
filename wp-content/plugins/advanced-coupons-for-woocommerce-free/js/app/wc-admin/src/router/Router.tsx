// #region [Imports] ===================================================================================================

import {createContext, useState, useEffect} from "@wordpress/element";
import {getHistory, getPath} from "../externals/wc-navigation";

// #endregion [Imports]

// #region [Interfaces] ================================================================================================

interface IProps {
  children: any;
}

// #endregion [Interfaces]

// #region [Contexts] =================================================================================================

export const RoutingContext = createContext({path: '', setPath: null});

// #endregion [Contexts]

// #region [Components] =================================================================================================

const Router = (props: IProps) => {
    
  const {children} = props;
  const [path, setPath]: [string, any] = useState('');
  const history = getHistory();

  // set initial path when app is loaded.
  useEffect(() => {
    setPath(getPath());
  },[]);

  // listen to route change.
  history.listen( (location: any) => {
    setPath(location.pathname);
  });

  return (
  <RoutingContext.Provider value={{path, setPath}}>
    {children}
  </RoutingContext.Provider>
  );
}

export default Router;

// #endregion [Components]