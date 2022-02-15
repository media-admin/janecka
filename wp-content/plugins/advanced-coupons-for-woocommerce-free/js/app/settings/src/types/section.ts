import { ISettingOption } from "./fields";

export interface ISection {
  id: string;
  title: string;
  fields: ISectionField[];
  show: boolean;
  module: string | boolean;
}

export interface ISectionField {
  title: string;
  type: string;
  desc?: string;
  desc_tip?: string;
  id: string;
  default?: any;
  options?: ISettingOption[];
  value: any;
  placeholder?: string;
  noticeData?: INoticeData;
  licenseContent?: string[];
}

export interface INoticeData {
  classname: string;
  title: string;
  description: string;
  button_link: string;
  button_text: string;
  button_class: string;
}
