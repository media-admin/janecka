// #region [Imports] ===================================================================================================

// Libraries
import { useCallback, useEffect, useRef } from "@wordpress/element";

// #endregion [Imports]

export default function useIsMounted() {
  const isMountedRef = useRef(true);
  const isMounted = useCallback(() => isMountedRef.current, []);

  useEffect(() => {
    return () => void (isMountedRef.current = false);
  }, []);

  return isMounted;
}
