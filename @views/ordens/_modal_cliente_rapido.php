<?php
/** Modal cadastro rápido de cliente — incluir apenas se Auth::isPerfilTecnico() */
?>
<div class="modal-backdrop" id="modalClienteRapido" aria-hidden="true">
    <div class="modal-dialog modal-cliente-rapido" role="dialog" aria-labelledby="modalClienteRapidoTitulo">
        <div class="modal-header">
            <h2 id="modalClienteRapidoTitulo" class="modal-title"><i class="fas fa-bolt"></i> Cliente rápido</h2>
            <button type="button" class="modal-close" id="btnFecharModalCliente" aria-label="Fechar">&times;</button>
        </div>
        <form id="formClienteRapido" novalidate>
            <div class="modal-body">
                <p class="modal-lead">Cadastre um cliente agora; ele será selecionado nesta OS.</p>
                <div class="form-group">
                    <label class="form-label required">Nome / Razão social</label>
                    <input type="text" name="nome_razao_social" id="cr_nome" class="form-control" required maxlength="200" autocomplete="organization">
                </div>
                <div class="form-group">
                    <label class="form-label">Nome fantasia</label>
                    <input type="text" name="nome_fantasia" id="cr_fantasia" class="form-control" maxlength="200">
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Tipo</label>
                        <select name="tipo_pessoa" id="cr_tipo" class="form-control">
                            <option value="juridica">Pessoa jurídica</option>
                            <option value="fisica">Pessoa física</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CPF / CNPJ</label>
                        <input type="text" name="documento" id="cr_doc" class="form-control" maxlength="18">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="cr_tel" class="form-control" maxlength="20">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Celular</label>
                        <input type="text" name="celular" id="cr_cel" class="form-control" maxlength="20">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" id="cr_email" class="form-control" maxlength="200">
                </div>
                <div class="form-msg form-msg-error" id="msgClienteRapidoErro" style="display:none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" id="btnCancelarModalCliente">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSalvarClienteRapido">
                    <i class="fas fa-check"></i> Cadastrar e selecionar
                </button>
            </div>
        </form>
    </div>
</div>
