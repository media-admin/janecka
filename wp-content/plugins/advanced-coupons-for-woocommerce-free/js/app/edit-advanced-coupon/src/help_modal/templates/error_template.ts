interface IErrorTemplateArguments {
  errorMsg: string;
}

export default function errorTemplate(response: IErrorTemplateArguments) {
  const { errorMsg } = response;
  return `
    <div class="acfw-help-modal-error">
      <p>${errorMsg}</p>
    </div>
  `;
}
