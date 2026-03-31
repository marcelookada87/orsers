<?php
/** @var array $cliente dados atuais (create usa vazio) */
$isEdit = !empty($cliente['id']);
$c = $cliente ?? [];
?>
<div class="form-grid">
    <div class="form-main">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Identificação</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label required">Nome / Razão social</label>
                    <input type="text" name="nome_razao_social" class="form-control" required maxlength="200"
                           value="<?= htmlspecialchars($c['nome_razao_social'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Nome fantasia</label>
                    <input type="text" name="nome_fantasia" class="form-control" maxlength="200"
                           value="<?= htmlspecialchars($c['nome_fantasia'] ?? '') ?>">
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Tipo</label>
                        <select name="tipo_pessoa" class="form-control">
                            <option value="juridica" <?= ($c['tipo_pessoa'] ?? 'juridica') === 'juridica' ? 'selected' : '' ?>>Pessoa jurídica</option>
                            <option value="fisica" <?= ($c['tipo_pessoa'] ?? '') === 'fisica' ? 'selected' : '' ?>>Pessoa física</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CPF / CNPJ</label>
                        <input type="text" name="documento" class="form-control" maxlength="18"
                               value="<?= htmlspecialchars($c['documento'] ?? '') ?>" placeholder="Somente números ou formatado">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Contato</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" maxlength="200"
                           value="<?= htmlspecialchars($c['email'] ?? '') ?>">
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" maxlength="20"
                               value="<?= htmlspecialchars($c['telefone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Celular</label>
                        <input type="text" name="celular" class="form-control" maxlength="20"
                               value="<?= htmlspecialchars($c['celular'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Endereço</h3></div>
            <div class="card-body">
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">CEP</label>
                        <input type="text" name="cep" class="form-control" maxlength="12"
                               value="<?= htmlspecialchars($c['cep'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">UF</label>
                        <input type="text" name="uf" class="form-control" maxlength="2" style="text-transform:uppercase"
                               value="<?= htmlspecialchars($c['uf'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Logradouro</label>
                    <input type="text" name="logradouro" class="form-control" maxlength="200"
                           value="<?= htmlspecialchars($c['logradouro'] ?? '') ?>">
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Número</label>
                        <input type="text" name="numero" class="form-control" maxlength="20"
                               value="<?= htmlspecialchars($c['numero'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Complemento</label>
                        <input type="text" name="complemento" class="form-control" maxlength="120"
                               value="<?= htmlspecialchars($c['complemento'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Bairro</label>
                        <input type="text" name="bairro" class="form-control" maxlength="120"
                               value="<?= htmlspecialchars($c['bairro'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade" class="form-control" maxlength="120"
                               value="<?= htmlspecialchars($c['cidade'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Observações</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <textarea name="observacoes" class="form-control" rows="4" placeholder="Notas internas sobre o cliente"><?= htmlspecialchars($c['observacoes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-sidebar">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Status</h3></div>
            <div class="card-body">
                <label class="checkbox-inline">
                    <input type="checkbox" name="ativo" value="1" <?= !isset($c['ativo']) || !empty($c['ativo']) ? 'checked' : '' ?>>
                    Cliente ativo (aparece nas listas de OS)
                </label>
            </div>
        </div>
        <?php if ($isEdit): ?>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save"></i> Salvar alterações
        </button>
        <?php else: ?>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save"></i> Cadastrar cliente
        </button>
        <?php endif; ?>
    </div>
</div>
