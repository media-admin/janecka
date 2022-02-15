// #region [Imports] ===================================================================================================

// Libraries
import "cross-fetch/polyfill";
import { put, call, takeEvery } from "redux-saga/effects";

// Types
import { ISettingValue } from "../../types/settings";

// Actions
import {
  ICreateSettingActionPayload,
  IUpdateSettingActionPayload,
  IDeleteSettingActionPayload,
  IReadSettingsActionPayload,
  IRehydrateStoreSettingsActionPayload,
  IRehydrateStoreSettingActionPayload,
  ESettingActionTypes,
  SettingActions,
} from "../actions/setting";
import { SectionActions } from "../actions/section";

// Helpers
import axiosInstance from "../../helpers/axios";

// #endregion [Imports]

// #region [Sagas] =====================================================================================================

export function* createSettingSaga(action: {
  type: string;
  payload: ICreateSettingActionPayload;
}): any {
  const { data, processingCB, successCB, failCB } = action.payload;

  try {
    if (typeof processingCB === "function") processingCB();

    const response = yield call(() =>
      axiosInstance.post(`coupons/v1/setting/sections`, data)
    );

    if (response && response.data) {
      yield put(SettingActions.rehydrateStoreSettings({}));

      if (typeof successCB === "function") successCB(response);
    }
  } catch (e) {
    if (typeof failCB === "function") failCB({ error: e, payload: data });
  }
}

export function* updateSettingSaga(action: {
  type: string;
  payload: IUpdateSettingActionPayload;
}): any {
  const { data, processingCB, successCB, failCB } = action.payload;

  try {
    if (typeof processingCB === "function") processingCB();

    if (!data.id)
      throw new Error("Can not update survey. No survey id provided.");

    const response = yield call(() => {
      const { id, ...updateData } = data;

      return axiosInstance.put(`coupons/v1/settings/${id}`, updateData);
    });

    if (response && response.data) {
      yield put(
        SettingActions.rehydrateStoreSetting({
          id: response.data.id,
          data: response.data,
        })
      );

      // toggle module display in settings nav.
      if ("module" === data.type) {
        yield put(
          SectionActions.toggleModuleSection({
            module: data.id,
            show: "yes" === data.value,
          })
        );
      }

      if (typeof successCB === "function") successCB(response);
    }
  } catch (e) {
    if (typeof failCB === "function") failCB({ error: e, payload: data });
  }
}

export function* deleteSettingSaga(action: {
  type: string;
  payload: IDeleteSettingActionPayload;
}): any {
  const { id, processingCB, successCB, failCB } = action.payload;

  try {
    if (typeof processingCB === "function") processingCB();

    const response = yield call(() =>
      axiosInstance.delete(`coupons/v1/settings/${id}`)
    );

    if (response && response.data) {
      yield put(SettingActions.rehydrateStoreSettings({}));

      if (typeof successCB === "function") successCB(response);
    }
  } catch (e) {
    if (typeof failCB === "function") failCB({ error: e });
  }
}

export function* readSettingSaga(action: {
  type: string;
  payload: IReadSettingsActionPayload;
}): any {
  const { processingCB, successCB, failCB } = action.payload;

  try {
    if (typeof processingCB === "function") processingCB();

    const response = yield call(() =>
      axiosInstance.get(`coupons/v1/settings/`)
    );

    if (response && response.data) {
      yield put(SettingActions.rehydrateStoreSettings({ data: response.data }));

      if (typeof successCB === "function") successCB(response);
    }
  } catch (e) {
    if (typeof failCB === "function") failCB({ error: e });
  }
}

export function* rehydrateStoreSettingsSaga(action: {
  type: string;
  payload: IRehydrateStoreSettingsActionPayload;
}): any {
  const { data, processingCB, successCB, failCB } = action.payload;

  try {
    if (typeof processingCB === "function") processingCB();

    if (data) {
      let values: ISettingValue[] = data.fields.filter(
        (f) => f.type !== "title" && f.type !== "sectionend"
      );
      values = values.map((f) => ({ id: f.id, value: f.value }));

      yield put(SettingActions.setStoreSettingItems({ data: values }));

      if (typeof successCB === "function") successCB(values);
    }
  } catch (e) {
    if (typeof failCB === "function") failCB({ error: e });
  }
}

export function* rehydrateStoreSettingSaga(action: {
  type: string;
  payload: IRehydrateStoreSettingActionPayload;
}): any {
  const { id, data, processingCB, successCB, failCB } = action.payload;

  try {
    if (typeof processingCB === "function") processingCB();

    if (!data) {
      const response = yield call(() =>
        axiosInstance.get(`coupons/v1/settings/${id}`)
      );

      if (response && response.data) {
        yield put(SettingActions.setStoreSettingItem({ data: response.data }));

        if (typeof successCB === "function") successCB(response);
      }
    } else {
      yield put(SettingActions.setStoreSettingItem({ data }));

      if (typeof successCB === "function") successCB(data);
    }
  } catch (e) {
    if (typeof failCB === "function") failCB({ error: e });
  }
}

// #endregion [Sagas]

// #region [Action Listeners] ==========================================================================================

export const actionListener = [
  takeEvery(ESettingActionTypes.CREATE_SETTING, createSettingSaga),
  takeEvery(ESettingActionTypes.UPDATE_SETTING, updateSettingSaga),
  takeEvery(ESettingActionTypes.DELETE_SETTING, deleteSettingSaga),
  takeEvery(ESettingActionTypes.READ_SETTINGS, readSettingSaga),
  takeEvery(
    ESettingActionTypes.REHYDRATE_STORE_SETTINGS,
    rehydrateStoreSettingsSaga
  ),
  takeEvery(
    ESettingActionTypes.REHYDRATE_STORE_SETTING,
    rehydrateStoreSettingSaga
  ),
];

// #endregion [Action Listeners]
